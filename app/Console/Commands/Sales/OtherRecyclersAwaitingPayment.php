<?php namespace App\Console\Commands\Sales;

use App\Invoice;
use App\Sale;
use App\Stock;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Exception;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;

class OtherRecyclersAwaitingPayment extends Command {

	protected $name = 'sales:other-recyclers-awaiting-payment';

	protected $description = 'Check if there are any Other Recycler Awaiting Payment oover 7 days orders.';

	public function fire()
	{
		$processed = 0;
		$sub7days = Carbon::now()->subWeek()->toDateString();
		$this->question("Other Recyclers Awaiting Payment - Before: ".$sub7days);

		$otherRecyclers = Sale::whereNotNull('other_recycler')->where('invoice_status', Invoice::STATUS_OPEN)->where('created_at', '<',$sub7days)->get();
		$data = [];
		if(!count($otherRecyclers)) {
			$this->info("Noting to Process");
			die;
		}
		foreach($otherRecyclers as $otherRecycler) {
			$items = [];
			$this->info("SaleID: $otherRecycler->id - $otherRecycler->other_recycler");
			foreach($otherRecycler->stock as $item) {
				$items[] = ['item_name' => $item->name, 'amount_expected' => $item->sale_price];
			}
			$data[] = [
				'recycler_name' => $otherRecycler->other_recycler,
				'recycler_order_number' => $otherRecycler->recyclers_order_number,
				'items' => $items
			];
			$processed++;
		}

		$data = json_decode(json_encode($data));
		try {
			$this->sendMail($data);
		} catch(Exception $e) {
			alert("Other Recyclers Awaiting Payment Exception: ".$e);
		}
		alert("Other Recyclers Awaiting Payment - processed: ".$processed);
		$this->question("Processed: ".$processed);
	}

	protected function sendMail($data)
	{

		Mail::send('emails.sales.other-recyclers-awaiting-payment', ['data' => $data], function (Message $mail) {
			$mail->subject("Please look into these Other Recycler Orders")
				->to(config('mail.chris_eaton.address'), config('mail.chris_eaton.name'))
				->from(config('mail.sales_address'), config('mail.from.name'));
		});
	}

}