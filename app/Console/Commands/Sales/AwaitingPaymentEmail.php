<?php namespace App\Console\Commands\Sales;


use App\Contracts\Invoicing;
use App\Jobs\ebay\CreateInvoice;
use App\Models\Invoice;
use App\Models\Sale;
use App\Models\SaleLog;
use App\Jobs\Sales\EmailSend;
use Illuminate\Console\Command;

class AwaitingPaymentEmail extends Command {

	protected $name = 'sales:awaiting-payment-email';

	protected $description = 'Send email for unpaid orders.';

	public function handle()
	{
		$sales = Sale::where('invoice_status', Invoice::STATUS_OPEN)->where('invoice_creation_status', Invoice::CREATION_STATUS_SUCCESS)->get();

		$total = count($sales);
		if(!$total) {
			$this->info("Nothing to Process.");
			return;
		}

		$this->info("Awaiting Payment Email: $total");
		$done = 0;


		foreach($sales as $sale) {
			$this->info("Sale $sale->id");
			//$this->info("$sale->id $sale->invoice_status $sale->invoice_creation_status");
		//	Queue::pushOn('emails', new EmailSend($sale, EmailSend::EMAIL_AWAITING_PAYMENT));

            dispatch(new EmailSend($sale, EmailSend::EMAIL_AWAITING_PAYMENT));

			$this->question('Email Sent');
			$done++;
			SaleLog::create([
				'sale_id' => $sale->id,
				'content' => "Awaiting Payment Email added to Emails Queue (cron)"
			]);
		}

		alert("Awaiting Payment Emails send: $done");

	}

}
