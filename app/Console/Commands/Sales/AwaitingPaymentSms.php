<?php namespace App\Console\Commands\Sales;

use App\Invoice;
use App\Sale;
use App\SaleLog;
use Illuminate\Console\Command;

class AwaitingPaymentSms extends Command {

	protected $name = 'sales:awaiting-payment-sms';

	protected $description = 'Send SMS for unpaid orders.';

	public function fire()
	{
		$sales = Sale::where('invoice_status', Invoice::STATUS_OPEN)->where('invoice_creation_status', Invoice::CREATION_STATUS_SUCCESS)->get();

		$total = count($sales);
		if(!$total) {
			$this->info("Nothing to Process.");
			return;
		}

		$this->info("Awaiting Payment SMS: $total");

		$done = 0;

		foreach($sales as $sale) {
			$this->info("Sale $sale->id");
			//$this->info("$sale->id $sale->invoice_status $sale->invoice_creation_status");
			$this->sendSms($sale);
			$done++;
		}

		$this->info("Awaiting Payment SMS send: $done");
		alert("Awaiting Payment SMS send: $done");

	}

	protected function sendSms($sale)
	{
		$invoicing = app('App\Contracts\Invoicing');
		$customer = $invoicing->getCustomer($sale->customer_api_id);

		if(!$customer) {
			$this->error("Sale $sale->id - no customer");
			return;
		}

		if (!$customer->phone && !$sale->user->phone) {
			$this->error("Sale $sale->id - no phone");
			return;
		}

		$phone = $customer->phone ? : $sale->user->phone;
		$name = $customer->first_name;
		$saleId = $sale->invoice_number;
		$amount = $sale->amount_formatted;

		$txtlocal = app('App\Contracts\Txtlocal');
		$sms = $txtlocal->sendAwaitingPayment($phone, $name, $saleId, $amount);
		$this->question("Sale $sale->id SMS Sent");
		SaleLog::create([
			'sale_id' => $sale->id,
			'content' => "Awaiting Payment SMS Sent (cron)"
		]);
		//$this->comment($sms);
	}

}
