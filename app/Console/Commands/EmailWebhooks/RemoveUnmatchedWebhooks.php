<?php namespace App\Console\Commands\EmailWebhooks;

use App\EmailWebhook;
use Illuminate\Console\Command;

class RemoveUnmatchedWebhooks extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'email-webhooks:remove-unmatched-webhooks';

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
		$webhooks = EmailWebhook::whereNull('email_tracking_id')->where('status', EmailWebhook::STATUS_PROCESSED)->limit(50)->get();

		if(!count($webhooks)) {
			$this->info("Nothing to Process");
			return;
		}

		$this->info("Webhooks Found: ".$webhooks->count());

		foreach($webhooks as $webhook) {
			$webhook->delete();
			$this->comment("Removed");
		}
	}

}
