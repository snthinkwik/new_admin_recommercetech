<?php namespace App\Console\Commands\ebay;

use App\Commands\ebay\CreateFeesSupplierBill;
use App\EbayOrders;
use Illuminate\Console\Command;
use Queue;

class CreateFeesSupplierBills extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'ebay:create-fees-supplier-bills';

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
		$orders = EbayOrders::whereNotNull('fees_invoice_number')->whereNull('supplier_bill_number')->limit(10)->get();
		
		if(!count($orders)) {
			$this->info("Nothing to Process");
			return;
		}
		
		$found = $orders->count();
		$this->info("Found: $found");
		$processed = 0;
		
		foreach($orders as $order) {
			$this->info("$order->id | $order->order_number | $order->sales_record_number");
			$paypalFees = $order->paypal_fees;
			$ebayFees = 0;
			$deliveryFees = 0;
			if($order->EbayFees->count()) {
				foreach($order->EbayFees as $fee) {
					if($fee->amount) {
						$ebayFees += str_replace("£", '', $fee->amount);
					}
				}
			}
			if($order->packaging_materials) {
				$deliveryFees += $order->packaging_materials;
			}
			if($order->EbayDeliveryCharges) {
				$deliveryFees += $order->EbayDeliveryCharges->cost;
			}
			if($order->DpdImport->count()) {
				$deliveryFees += $order->DpdImport->sum('cost');
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
				'ebay_order_id' => $order->id,
				'ebay_order_number' => $order->order_number,
				'ebay_order_sales_record_number' => $order->sales_record_number
			];
			
			$dataJsonDecode = json_decode(json_encode($data));
			
			$supplierId = 8;// local 75;
			Queue::pushOn('invoices', new CreateFeesSupplierBill($order, $supplierId, $dataJsonDecode));
			$processed++;
		}
		
		$this->info("Processed: $processed | Found: $found");
	}

}
