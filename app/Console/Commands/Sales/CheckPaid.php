<?php namespace App\Console\Commands\Sales;

use App\Events\Sale\Cancelled;
use App\Invoice;
use App\Sale;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Queue;

class CheckPaid extends Command {

	protected $name = 'sales:check-paid';

	protected $description = 'Command description.';

	public function fire()
	{
		$invoicing = app('App\Contracts\Invoicing');
		$invoicing->setCacheTime(0);
		$sales = Sale::where('invoice_status', 'open')->where('invoice_creation_status', 'success')->get();
		if (!count($sales)) {
			return;
		}
		
		$invoices = $invoicing->getInvoices($sales->lists('invoice_api_id'))->keyBy('api_id');

		foreach ($sales as $sale) {
			if (!isset($invoices[$sale->invoice_api_id])) {
				alert(
					"Sale \"$sale->id\" with API invoice id \"$sale->invoice_api_id\" doesn't have a corresponding API invoice."
				);
				continue;
			}
			
			$apiInvoice = $invoices[$sale->invoice_api_id];

			if ($apiInvoice->status === Invoice::STATUS_PAID) {
				$sale->invoice_status = Invoice::STATUS_PAID;
				$sale->save();
			}
		}
	}

}
