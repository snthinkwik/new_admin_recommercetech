<?php namespace App\Console\Commands\EmailWebhooks;

use App\EmailWebhook;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class ProcessType extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'email-webhooks:process-type';

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
		$webhooks = EmailWebhook::where('type', '')->limit(50)->get();

		if(!count($webhooks)) {
			$this->info("Nothing to Process");
			return;
		}

		$this->info("Webhooks Found: ".$webhooks->count());

		foreach($webhooks as $webhook) {
			$data = json_decode($webhook->response);
			$type = $data->{'event-data'}->event;
			$webhook->type = $type;
			if($type == EmailWebhook::EVENT_FAILED) {
				//check if it's temporary or permanent
				$failedCheck = $data->{'event-data'}->severity;
				$this->comment($failedCheck);
				if($failedCheck == "temporary") {
					$webhook->type = EmailWebhook::EVENT_TEMPORARY_FAILED;
				}
			}
			$webhook->save();

			$this->question("Event $webhook->type");
		}
	}

}
