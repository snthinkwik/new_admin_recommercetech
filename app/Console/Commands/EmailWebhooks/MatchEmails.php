<?php namespace App\Console\Commands\EmailWebhooks;

use App\EmailTracking;
use App\EmailWebhook;
use Illuminate\Console\Command;
use Carbon\Carbon;
use Exception;

class MatchEmails extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'email-webhooks:match-emails';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Command description.';

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
		$processInfo = `ps aux`;
		$workerCommand = 'email-webhooks:match-emails';

		$checkRunning = substr_count($processInfo, $workerCommand);
		$this->comment($checkRunning);

		if ($checkRunning > 1) {
			$this->info("Already running");
			return;
		}

		$webhooks = EmailWebhook::whereNull('email_tracking_id')->where('type', '!=', '')->where('status', EmailWebhook::STATUS_NEW)->limit(50)->get();

		if(!count($webhooks)) {
			$this->info("Nothing Found");
			return;
		}

		$this->info("Webhooks Found: ".$webhooks->count());

		foreach($webhooks as $webhook) {
			$data = json_decode($webhook->response);
			$messageId = $data->{'event-data'}->message->headers->{'message-id'};
			$eventTime = $data->signature->timestamp;
			$this->question($messageId." - ".$eventTime);
			$webhook->event_time = $eventTime;
			if(EmailTracking::where('message_id', $messageId)->first()) {
				$this->comment("Email Tracking Found");
				$webhook->email_tracking_id = EmailTracking::where('message_id', $messageId)->first()->id;
				$webhook->status = EmailWebhook::STATUS_PROCESSED;
			} else {
				$webhook->status = EmailWebhook::STATUS_PROCESSED;
			}
			$webhook->save();

			//if type = failed (permanent fail), disable user marketing emails
			if($webhook->type == EmailWebhook::EVENT_FAILED && $webhook->email_tracking_id && $webhook->email_tracking->user_id) {
				$user = $webhook->email_tracking->user;
				try {
					if ($user->marketing_emails_subscribe) {
						$user->marketing_emails_subscribe = false;
						$user->save();
						$this->question("Marketing Emails Subscribtion - disabled");
					}
				} catch (Exception $e) {
					$this->info("Exception $e");
					alert("Match Emails Exception ($webhook->id) - ".$e);
				}
			}
		}
	}

}
