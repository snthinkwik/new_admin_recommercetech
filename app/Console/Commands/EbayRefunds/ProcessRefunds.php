<?php namespace App\Console\Commands\EbayRefunds;

use App\Commands\EbayRefunds\CreateCreditNote;
use App\EbayOrderItems;
use App\EbayRefund;
use App\Invoicing;
use Queue;
use Illuminate\Console\Command;

class ProcessRefunds extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'ebay-refunds:process-refunds';

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
		$ebayRefunds = EbayRefund::where('processed', 'No')->whereNull('credit_note_number')->where('owner', EbayOrderItems::RECOMM)->limit(50)->get();
		
		if(!count($ebayRefunds)) {
			$this->info("Nothing to Process");
			return;
		}
		
		$found = $ebayRefunds->count();
		$customerId = 447; //71 (dev)
		$processed = 0;
		
		
		foreach($ebayRefunds as $refund) {
			$order = $refund->order;
			$this->info("Refund ID: $refund->id | $refund->owner | $refund->refund_amount | $refund->processed | ".$order->EbayOrderItems()->count());
			Queue::pushOn('invoices', new CreateCreditNote($refund, $customerId, Invoicing::EBAY_RETURNS));
			$processed++;
		}
		
		$this->info("Processed: $processed / $found");
	}

}
