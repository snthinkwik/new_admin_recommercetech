<?php namespace App\Console\Commands\Quickbooks;

use App\Invoicing\Quickbooks\WebhookEvent;
use App\Sale;
use Illuminate\Console\Command;

class ProcessWebhooks extends Command {

	protected $name = 'quickbooks:process-webhooks';

	protected $description = 'Process webhook notifications delivered to use from Quickbooks';

	public function fire()
	{
		$events = WebhookEvent::where('status', WebhookEvent::STATUS_NEW)->get();
		if (!count($events)) {
			die("Nothing to process.\n");
		}

		$i = 0;
		$n = count($events);
		foreach ($events as $event) {
			$i++;
			progress($i, $n);

			if (!isset($event->payload['eventNotifications'])) {
				continue;
			}

			foreach ($event->payload['eventNotifications'] as $notification) {
				if (!isset($notification['dataChangeEvent']['entities'])) {
					continue;
				}

				foreach ($notification['dataChangeEvent']['entities'] as $entity) {
					if (!isset($entity['name'], $entity['id'], $entity['operation'])) {
						continue;
					}

					if ($entity['name'] === 'Invoice' && $entity['operation'] === 'Delete') {
						$sale = Sale::where('invoice_api_id', $entity['id'])->first();
						if ($sale) {
							$sale->delete();
						}
					}
				}
			}
			$event->status = WebhookEvent::STATUS_PROCESSED;
			$event->save();
		}
	}

}
