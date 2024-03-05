<?php namespace App\Console\Commands\Sales;

use App\Invoice;
use App\Sale;
use Illuminate\Console\Command;
use Carbon\Carbon;

class OtherRecyclersAwaitingPayment14days extends Command {

	protected $name = 'sales:other-recyclers-awaiting-payment-14-days';

	protected $description = 'Changes over 14 days unpaid orders to paid.';


	public function fire()
	{
		$sub14days = Carbon::now()->subDays(14)->toDateString();
		$this->info("Sub 14 Days: ".$sub14days);

		$otherRecyclers = Sale::whereNotNull('other_recycler')->where('invoice_status', Invoice::STATUS_OPEN)->where('created_at', '<',$sub14days)->get();

		if(!count($otherRecyclers)) {
			$this->info("Nothing to Process");
			return;
		}

		$this->info("Other Recyclers: ".count($otherRecyclers));

		$ids = [];

		foreach($otherRecyclers as $otherRecycler) {
			$otherRecycler->invoice_status = Invoice::STATUS_PAID;
			$otherRecycler->save();
			$this->question("Paid: ".$otherRecycler->id);
			$ids[] = $otherRecycler->id;
		}
		$count = count($ids);
		$ids = implode(", ", $ids);
		$this->info($ids);
		alert("Other Recyclers Over 14 Days - $count : ".$ids);
	}

}
