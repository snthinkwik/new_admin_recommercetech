<?php namespace App\Console\Commands\Batches;

use App\Batch;
use App\Stock;
use App\SysLog;
use App\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Setting;
use Symfony\Component\Console\Input\InputOption;

class Create extends Command {

	protected $name = 'batches:create';

	protected $description = 'Create a stock batch.';

	public function fire()
	{
		$runOnce = $this->option('run-once') === 'true' ? true : false;

		if (!Setting::get('crons.batches.enabled', true) && !$runOnce) {
			die("Cron turned off. Existing\n");
		}

		if ($this->option('remove-batch-locks')) {
			Stock::where('locked_by', 'like', 'batch_%')->update([
				'locked_by' => '',
				'status' => Stock::STATUS_IN_STOCK,
				'batch_id' => null,
			]);
		}

		$batch = $this->makeBatch();
		if ($batch) {
			$this->sendEmail($batch);
		}
		else {
			die("No items were found for batch, so no email was sent.\n");
		}
	}

	protected function sendEmail(Batch $batch)
	{
		$stock = $batch->stock()->orderBy('name')->get();
		if (!count($stock)) return;

		$csvPath = $this->getListCsv($stock);

		if ($this->option('emails')) {
			$usersQuery = User::whereIn('email', $this->option('emails'));
		}
		else {
			$usersQuery = User::withUnregistered()->where('type', '=', 'user')->where('marketing_emails_subscribe', true);
		}

		$users = $usersQuery->get();
		$userCount = count($users);

		foreach ($users as $i => $user) {
			try {
				Mail::send(
					'emails.batches.new-batch',
					compact('user', 'batch'),
					function (Message $mail) use ($user, $stock, $csvPath, $batch) {
						$mail->subject("Batch No. $batch->id Customer Returns  Auction - Submit your offer by 3pm Monday")
							->to($user->email, $user->full_name ?: null)
							->from(
								Setting::get('crons.batches.from.email') ?: config('mail.sales_address'),
								Setting::get('crons.batches.from.name') ?: config('mail.chris_eaton.name')
							)
							->attach($csvPath);
					}
				);
			}
			catch (Exception $e) {
				alert("Batch stock email error while sending to user id \"$user->id\": \n\n$e");
				throw $e;
			}
			progress($i + 1, $userCount);
		}
	}

	/**
	 * @return Batch
	 */
	protected function makeBatch()
	{
		$lockKey = substr('batch_' . md5(rand()), 0, 32);
		// Lock our stock to prevent people from buying it in-between our queries.
		Stock::status(Stock::STATUS_IN_STOCK)
			->where('purchase_date', '<', Carbon::now()->subDays(7))
			->where('locked_by', '')
			->update(['locked_by' => $lockKey]);
		$stock = Stock::where('locked_by', $lockKey)->get();
		if (!count($stock)) return false;
		SysLog::log("Locking with key \"$lockKey\".", null, $stock->lists('id'));

		$batch = Batch::create([]);
		foreach ($stock as $item) {
			$item->status = Stock::STATUS_BATCH;
			$item->batch_id = $batch->id;
			$item->save();
		}

		return $batch;
	}

	/**
	 * @param Collection $stock
	 * @return string Path to CSV. Remember to delete it after sending the email.
	 */
	protected function getListCsv($stock)
	{
		$fields = [
			'RCT Ref' => 'our_ref',
			'Name' => 'name',
			'Capacity' => 'capacity_formatted',
			'Colour' => 'colour',
			'Grade' => 'grade',
			'Network' => 'network',
			'Engineer notes' => 'notes',
		];

		$csvPath = tempnam('/tmp', 'stock-device-list-');
		unlink($csvPath);
		$csvPath .= '.csv';
		$fh = fopen($csvPath, 'w');
		fputcsv($fh, array_keys($fields));
		foreach ($stock as $item) {
			$row = array_map(function($field) use($item) { return $item->$field; }, $fields);
			fputcsv($fh, $row);
		}
		fclose($fh);
		shell_exec("iconv -f UTF-8 -t ISO-8859-1 $csvPath > $csvPath.converted");
		unlink($csvPath);
		rename("$csvPath.converted", $csvPath);
		return $csvPath;
	}

	public function getOptions()
	{
		return [
			[
				'remove-batch-locks',
				'r',
				InputOption::VALUE_NONE,
				'Should we unlock database locks that were locked by batches. Only for development.',
			],
			[
				'emails',
				'e',
				InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
				'Optional email addresses to which the email should be sent.'
			],
			[
				'run-once',
				'run-once',
				InputOption::VALUE_OPTIONAL,
				'Run Once - for running from AdminSettings->Cron'
			]
		];
	}

}
