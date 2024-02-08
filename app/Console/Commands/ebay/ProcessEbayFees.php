<?php namespace App\Console\Commands\ebay;

use App\Commands\ebay\CreateFeesSupplierBill;
use App\EbayOrderItems;
use Illuminate\Console\Command;
use Queue;

class ProcessEbayFees extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'ebay:process-ebay-fees';

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
		$orderItems = EbayOrderItems::readyForInvoice()->whereNotNull('invoice_number')
			->whereHas('order', function($q) {
				$q->where('paypal_fees', '>', 0);
				$q->has('EbayFees');
				$q->where(function($d) {
					$d->has('DpdImport');
					$d->orHas('EbayDeliveryCharges');
					$d->orWhere('packaging_materials', '>', 0);
				});
				$q->whereNull('fees_invoice_number');
			})
			->orderBy('id', 'desc')->limit(10)->get();

		if(!count($orderItems)) {
			$this->info("Nothing to Process");
			return;
		}
		$found = $orderItems->count();
		$processed = 0;
		$this->info("Found: $found");

		$trgStock = app('App\Contracts\TrgStock');

		foreach($orderItems as $orderItem) {
			if($orderItem->order->fees_invoice_number) {
				$this->comment("fees invoice number already exists");
				continue;
			}
			$this->info("$orderItem->id | ".$orderItem->order->id." | ".$orderItem->order->order_number." | ".$orderItem->order->sales_record_number);
			$paypalFees = $orderItem->order->paypal_fees;
			$ebayFees = 0;
			$deliveryFees = 0;
			if($orderItem->order->EbayFees->count()) {
				foreach($orderItem->order->EbayFees as $fee) {
					if($fee->amount) {
						$ebayFees += str_replace("£", '', $fee->amount);
					}
				}
			}
			if($orderItem->order->packaging_materials) {
				$deliveryFees += $orderItem->order->packaging_materials;
			}
			if($orderItem->order->EbayDeliveryCharges) {
				$deliveryFees += $orderItem->order->EbayDeliveryCharges->cost;
			}
			if($orderItem->order->DpdImport->count()) {
				$deliveryFees += $orderItem->order->DpdImport->sum('cost');
			}
			$this->comment("PayPal: $paypalFees | DeliveryFees: $deliveryFees | EbayFees: $ebayFees");
			if($paypalFees == 0 || $deliveryFees == 0 || $ebayFees == 0) {
				$this->error("Invalid");;
				continue;
			}
			
			$data = [
				'paypal' => $paypalFees,
				'delivery' => $deliveryFees,
				'ebay' => $ebayFees,
				'ebay_order_id' => $orderItem->order->id,
				'ebay_order_number' => $orderItem->order->order_number,
				'ebay_order_sales_record_number' => $orderItem->order->sales_record_number
			];
			
			$dataJson = json_encode($data);
			$dataJsonDecode = json_decode($dataJson);
			
			$res = $trgStock->createEbayFeesInvoice($dataJson);
			
			if($res->status == 'success') {
				$data = json_decode($res->data);
				$invoiceNumber = $data->id;
				$orderItem->order->fees_invoice_number = $invoiceNumber;
				$orderItem->order->save();
				$processed++;
			}
			$this->info(json_encode($res));
			
		}
		$this->info("Processed: $processed | Found: $found");
		
	}

}
