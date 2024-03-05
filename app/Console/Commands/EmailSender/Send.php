<?php namespace App\Console\Commands\EmailSender;

use App\Models\Batch;
use App\Models\Email;
use App\Models\Invoice;
use App\Models\Sale;
use App\Models\User;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Carbon\Carbon;

class Send extends Command {

	protected $name = 'email-sender:send';

	protected $description = 'Send email ';

	/**
	 * @var Email
	 */
	protected $email;

	protected $attachment;

	public function handle()
	{
		$this->setDataFromInput();

		$lastUpdate = time();
		$email = $this->email;
		$email->status = Email::STATUS_SENDING;
		$email->status_details = 'Started sending';
		$email->save();
		$attachment = $this->attachment;
		$errorCount = 0;
		$errorGuzzleCount = 0;

		try {
			$userQuery = User::query();
			if(!$email->option) {
				$this->question("No option");
				if ($email->to === Email::TO_EVERYONE) {
					$userQuery = $userQuery->withUnregistered();
				} elseif ($email->to === Email::TO_UNREGISTERED) {
					$userQuery = $userQuery->unregistered();
				}
			}
			$userQuery->where('marketing_emails_subscribe', true);


			if($email->option) {
				$this->info("Option Detected $email->option");
				if ($email->option === Email::OPTION_COUNTRY) {
					if (!$email->option_details) {
						$this->error("Country but Country not specified.");
						$email->status = Email::STATUS_ERROR;
						$email->status_details = "Option Country but country is not specified";
						$email->save();
						return;
					}
					$country = $email->option_details;
					$userQuery->whereHas('address', function ($query) use ($country) {
						$query->where('country', $country);
					});
				} elseif($email->option === Email::OPTION_NEVER_BOUGHT) {
					$usersNeverBoughtIds = Sale::groupBy('user_id')->get()->lists('user_id');
					$userQuery->whereNotIn('id', $usersNeverBoughtIds);
				} elseif($email->option === Email::OPTION_BOUGHT_NOT_LAST_45_DAYS) {
					$date45daysAgo = Carbon::now()->subDays(45)->startOfDay();
					$usersEverBoughtIds = Sale::groupBy('user_id')->get()->lists('user_id');
					$usersLast45daysIds = Sale::where('created_at', '>=',$date45daysAgo)->groupBy('user_id')->get()->lists('user_id');
					$userQuery->whereIn('id', $usersEverBoughtIds)->whereNotIn('id', $usersLast45daysIds);
				} elseif($email->option === Email::OPTION_PAID_NOT_DISPATCHED) {
					$usersPaidNotDispatched = Sale::whereIn('invoice_status', [Invoice::STATUS_PAID, Invoice::STATUS_READY_FOR_DISPATCH])->groupBy('user_id')->get()->lists('user_id');
					$userQuery->whereIn('id', $usersPaidNotDispatched);
				}
			}


			$totalCount = $userQuery->count();
			$doneCount = 0;

			$userQuery->chunk(500, function($users) use ($email, &$doneCount, &$lastUpdate, $totalCount, $attachment, &$errorCount, &$errorGuzzleCount) {
				foreach ($users as $user) {
					try {
						if (filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
							$this->sendEmail($email, $user, $attachment);
						}
						$doneCount++;
						if (time() - $lastUpdate > 5) {
							$email->status_details = "Sent to $doneCount out of $totalCount users.";
							$email->save();
							$lastUpdate = time();
						}
						progress($doneCount, $totalCount);
					} catch (Exception $e) {
						if($e instanceof \GuzzleHttp\Exception\ClientException) {
							$errorGuzzleCount++;
							$this->question("Guzzle Exception $errorGuzzleCount");
							if($errorGuzzleCount==1) {
								$this->question("User $user->id saving guzzle exception");
								//file_put_contents("/tmp/debug-exception", print_r($e, 1) . "\n", FILE_APPEND);
							}
						}
						$errorCount++;
						$this->question("ErrorCount: $errorCount - $errorGuzzleCount");
						$this->error("User $user->id Exception: ".$e->getMessage());
						//file_put_contents("/tmp/debug-email-exceptions", "User $user->id Exception: ".$e->getMessage()."\n", FILE_APPEND);
					}
				}
			});

			$email->status = Email::STATUS_SENT;
			$email->status_details = "Sent to $doneCount out of $totalCount users.";
			$email->save();
			if($attachment && strpos($attachment['path'], 'tmpSend') !== false)
				unlink($attachment['path']);

			if($email->attachment == Email::ATTACHMENT_FILES && count($email->files)) {
				foreach($email->files as $name => $path) {
					unlink($path);
				}
			}
		}
		catch (Exception $e) {
			$email->status = Email::STATUS_ERROR;
			$email->status_details = "$e";
			$email->save();
			throw $e;
		}

		if($errorCount > 0 || $errorGuzzleCount > 0) {
			alert("Email Sender - Errors: $errorCount , Guzzle Errors: $errorGuzzleCount");
		}
	}

	protected function sendEmail(Email $email, User $user, $attachment)
	{
	    $encryptid=Crypt::encrypt($user->id);

	    if($attachment)
			$template = 'emails.batches.send-batch';
		else
			$template = 'emails.email-sender.marketing-message';
		Mail::send(
			$template,
			['body' => Email::getBodyHtml($email->body, $user), 'fromName' => $email->from_name, 'user' => $user, 'brand' => $email->brand,'encrypt_id'=>$encryptid],
			function (Message $message) use($email, $user, $attachment) {
				$message->from($email->from_email, $email->from_name)
					->to($user->email, $user->full_name)
					->subject(Email::getSubjectHtml($email->subject, $user))
					->getSwiftMessage()
					->getHeaders()
					->addTextHeader('email_id', $email->id);
				if ($email->attachment === Email::ATTACHMENT_FILE) {
					$message->attach($email->file_path);
				}
				elseif ($email->attachment === Email::ATTACHMENT_BATCH) {
					$storagePath = base_path('public/files/tmpSend/');
					$batch = Batch::findOrFail($email->batch_id);
					$xls = $batch->getXls('batch');
					$xls->store('xls', $storagePath);
					$message->attach($storagePath . $xls->filename . '.' . $xls->ext, ['as' => "Batch.$xls->ext"]);
				}
				elseif ($attachment) {
					$message->attach($attachment['path'] ,['as' => $attachment['name']]);
				}
				elseif ($email->attachment = Email::ATTACHMENT_FILES && count($email->files)) {
					foreach($email->files as $name => $path) {
						$message->attach($path ,['as' => $name]);
					}
				}
			}
		);
	}

	protected function setDataFromInput()
	{
		$this->email = Email::findOrFail($this->argument('email-id'));
		if ($this->option('attachment-path') ||  $this->option('attachment-name')) {
			// If one of them is specified then they both have to be.
			if (!$this->option('attachment-path') || !$this->option('attachment-name')) {
				throw new Exception("If attachment path or name is specified then they both have to be specified.");
			}
			$this->attachment = ['path' => $this->option('attachment-path'), 'name' => $this->option('attachment-name')];
		}
	}

	protected function getArguments()
	{
		return [
			['email-id', InputArgument::REQUIRED, 'Id of the email model'],
		];
	}

	protected function getOptions()
	{
		return [
			[
				'attachment-path',
				null,
				InputOption::VALUE_REQUIRED,
				'Path to the optional attachment (must be specified along with attachment-name option).',
				null
			],
			[
				'attachment-name',
				null,
				InputOption::VALUE_REQUIRED,
				'Path to the optional attachment (must be specified along with attachment-path option).',
				null
			],
		];
	}

}
