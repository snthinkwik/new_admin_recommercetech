<?php namespace App\Console\Commands\MobileAdvantage;

use App\Models\EbayOrderLog;
use App\Models\EbayOrders;
use App\Models\HistoryLog;
use App\Models\Stock;
use App\Models\EbayOrderItems;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class SyncAllOrders extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'mobile-advantage:sync-order';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Command description.';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function handle()
	{



        $startDate = date('Y-m-d H:i:s');
        $client_id = config('services.mobile_advantage.client_id');
        $client_secret = config('services.mobile_advantage.secret');
        $client_device=config('services.mobile_advantage.device');

        $header = array(
            'Accept: application/json',
           // 'Content-Type: application/x-www-form-urlencoded',
            'x-client-id:'.$client_id,
            'x-client-secret:'.$client_secret,
            'x-client-device:'.$client_device,
        );
        $i = 1;
        $d = 0;

        do {
            $mobileAdvantage = getMobileAdvantageData($header, $i);
            $j = ceil((isset($mobileAdvantage['data']->totalCount) ? $mobileAdvantage['data']->totalCount : 0) / (isset($mobileAdvantage['data']->limit) ? $mobileAdvantage['data']->limit : 1));
            if (count($mobileAdvantage['data']->result)>0) {
                foreach ($mobileAdvantage['data']->result as $mobileAdvantage) {
                        $Order = EbayOrders::firstOrNew([
                            'sales_record_number' => $mobileAdvantage->order_id,
                            'platform'=>Stock::PLATFROM_MOBILE_ADVANTAGE,
                        ]);

                       // $orderId=  substr($mobileAdvantage->id, strpos($mobileAdvantage->id, "-") + 1);

                        $Order->sales_record_number = $mobileAdvantage->order_id;
                      //  $Order->order_number = '-';
                        $Order->order_id=$mobileAdvantage->order_id;
                        $Order->buyer_name = $mobileAdvantage->user_name;
                        $Order->buyer_email = $mobileAdvantage->shipping_email;
                        //$Order->buyer_note = $mobileAdvantage->buyerMessage;
                        $Order->buyer_address_1 = $mobileAdvantage->billing_address_1;
                      //  $Order->buyer_address_2 = $mobileAdvantage->billingAddress2;
                        $Order->buyer_city = $mobileAdvantage->billing_city;
                     //   $Order->buyer_county = '-';
                        $Order->buyer_postcode = $mobileAdvantage->billing_postcode;
                        $Order->buyer_country = $mobileAdvantage->billing_country;
                        $Order->post_to_name =  $mobileAdvantage->user_name;
                        $Order->post_to_phone = $mobileAdvantage->phone_number;
                        $Order->post_to_address_1 = $mobileAdvantage->shipping_address_1;
                       // $Order->post_to_address_2 = $mobileAdvantage->shippingAddress2;
                        $Order->post_to_city = $mobileAdvantage->shipping_city;
                      //  $Order->post_to_county = $mobileAdvantage->shippingAddressCounty;
                        $Order->post_to_postcode = $mobileAdvantage->shipping_postcode;
                        $Order->post_to_country = $mobileAdvantage->shipping_country;
                       $Order->postage_and_packaging = $mobileAdvantage->shippingCost;
                        $Order->total_price = $mobileAdvantage->total;
                        $Order->payment_method =isset($mobileAdvantage->payment_method)? $mobileAdvantage->payment_method:null;
                        $Order->sale_date = isset($mobileAdvantage->paid_on_date)?$mobileAdvantage->paid_on_date:'';
                        $Order->paid_on_date =isset($mobileAdvantage->paid_on_date)? $mobileAdvantage->paid_on_date:'';
                        $Order->shipping_email = $mobileAdvantage->email;
                        $Order->transaction_id = isset($mobileAdvantage->transaction_id)?$mobileAdvantage->transaction_id:'';
                        $Order->tracking_number=isset($mobileAdvantage->tracking_number)?$mobileAdvantage->tracking_number:'';
                        $Order->status=$mobileAdvantage->status;
                        $Order->payment_type=isset($mobileAdvantage->klarna_payment_method_used)?$mobileAdvantage->klarna_payment_method_used:null;
                        $Order->billing_phone_number=isset($mobileAdvantage->shipping_phone_number)?$mobileAdvantage->shipping_phone_number:null;

                        $Order->platform =Stock::PLATFROM_MOBILE_ADVANTAGE;

                        $changesBayOrder = '';

                        if ($Order->exists) {
                            if ($Order->isDirty()) {
                                foreach ($Order->getAttributes() as $key => $value) {
                                    if ($value !== $Order->getOriginal($key) && !checkUpdatedFields($value, $Order->getOriginal($key))) {
                                        $orgVal = $Order->getOriginal($key);
                                        $changesBayOrder .= "Changed \"$key\" from \"$orgVal\" to \"$value\".\n";
                                    }
                                }

                                $Order->save();
                            }
                        } else {
                            $changesBayOrder .= "Imported New Order. Sales record number: " . $Order->sales_record_number . " with Status: " . $Order->status . "\n";
                            $Order->save();
                            $d++;
                        }
                        foreach ($mobileAdvantage->product_information as $item) {
                            $OrderItem = EbayOrderItems::firstOrNew([
                                'item_id' => $item->item_id,
                                'order_id' => $Order->id
                            ]);

                            $OrderItem->order_id = $Order->id;
                            $OrderItem->sales_record_number = $Order->sales_record_number;
                            $OrderItem->item_id = $item->item_id;
                            $OrderItem->item_name = $item->item_name;
                            $OrderItem->item_sku = $item->item_sku;
                            $OrderItem->quantity = $item->quantity;
                            $OrderItem->individual_item_price = $item->item_price;
                            $OrderItem->item_image = isset($item->item_image_path)?$item->item_image_path:'';

                            if ($OrderItem->exists) {
                                if ($OrderItem->isDirty()) {
                                    foreach ($OrderItem->getAttributes() as $key => $value) {
                                        if ($value !== $OrderItem->getOriginal($key) && !checkUpdatedFields($value, $OrderItem->getOriginal($key))) {
                                            $orgVal = $OrderItem->getOriginal($key);
                                            $changesBayOrder .= "Changed \"$key\" from \"$orgVal\" to \"$value\".\n";
                                        }
                                    }

                                    $OrderItem->save();
                                }
                            } else {
                                $changesBayOrder .= "Imported New Order Items. Item name: " . $OrderItem->item_name . "\n";
                                $OrderItem->save();
                            }
                        }

                        if (!empty($changesBayOrder)) {
                            $this->comment($changesBayOrder);

                            $ebayOrdersLogModel = new EbayOrderLog();
                            $ebayOrdersLogModel->orders_id = $Order->id;
                            $ebayOrdersLogModel->content = $changesBayOrder;
                            $ebayOrdersLogModel->save();
                        }
                    }

            }

            $i++;
        } while ($j >= $i);

        $endDate = date('Y-m-d H:i:s');

        $historyLogModel = new HistoryLog();
        $historyLogModel->script_started = $startDate;
        $historyLogModel->script_finished = $endDate;
        $historyLogModel->import_count = $d;
        $historyLogModel->save();
    }










}
