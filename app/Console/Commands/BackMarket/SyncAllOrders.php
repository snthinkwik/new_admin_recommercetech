<?php namespace App\Console\Commands\BackMarket;

use App\Models\EbayOrderLog;
use App\Models\EbayOrders;
use App\Models\HistoryLog;
use App\Models\Stock;
use App\Models\EbayOrderItems;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use GuzzleHttp\Client;


class SyncAllOrders extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'back-market:sync-all-orders';

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


        $header = array(
            "Authorization:Basic ".config('services.back_market.token')
        );

        $i = 1;
        $d = 0;
        $startDate = date('Y-m-d H:i:s');
        do {
            $eBayApiResponse = getBackMarketOrderData($header, $i);

            $j = ceil((isset($eBayApiResponse["count"]) ? $eBayApiResponse["count"] : 0) / (10));


            if (isset($eBayApiResponse["results"])) {
                foreach ($eBayApiResponse["results"] as $backMarket) {

                    if (isset($backMarket->order_id)) {

                        $Order = EbayOrders::firstOrNew([
                            'sales_record_number' => $backMarket->order_id
                        ]);

                        $status='';

                        foreach ($backMarket->orderlines as $item) {


                            if (!$item->state || $item->state == 1) {
                                $status = EbayOrders::STATUS_NEW;
                            } elseif ($item->state == 2 || $item->state == 3) {
                                $status = EbayOrders::STATUS_DISPATCHED;
                            } elseif ($item->state == 5 || $item->state == 6 || $item->state == 7) {
                                $status = EbayOrders::STATUS_REFUNDED;
                            } elseif ($item->state == 4) {
                                $status = EbayOrders::STATUS_CANCELLED;
                            }

                        }


                        $Order->sales_record_number = $backMarket->order_id;
                       // $Order->order_number = '';
                        $Order->order_id=$backMarket->order_id;
                        $Order->buyer_name =$backMarket->billing_address->first_name.' '. $backMarket->billing_address->last_name;
                        $Order->buyer_email = $backMarket->billing_address->email;
                      //  $Order->buyer_note = '';
                        $Order->buyer_address_1 = $backMarket->billing_address->street;
                        $Order->buyer_address_2 = $backMarket->billing_address->street2;
                        $Order->buyer_city = $backMarket->billing_address->city;
                      //  $Order->buyer_county = '';
                        $Order->buyer_postcode = $backMarket->billing_address->postal_code;
                        $Order->buyer_country = getCountry($backMarket->billing_address->country) ;
                        $Order->post_to_name = $backMarket->shipping_address->first_name.' '. $backMarket->shipping_address->last_name;
                        $Order->post_to_phone = $backMarket->shipping_address->phone;
                        $Order->post_to_address_1 = $backMarket->shipping_address->street;
                        $Order->post_to_address_2 = $backMarket->shipping_address->street2;
                        $Order->post_to_city = $backMarket->shipping_address->city;
                        //   $Order->post_to_county = '';
                        $Order->post_to_postcode = $backMarket->shipping_address->postal_code;
                        $Order->post_to_country = getCountry($backMarket->shipping_address->country);
                        $Order->postage_and_packaging = $backMarket->shipping_price;
                        $Order->total_price = $backMarket->price;
                        $Order->payment_method = $backMarket->payment_method;
                        $Order->sale_date = $backMarket->date_creation;
                        $Order->paid_on_date = $backMarket->date_payment;
                        $Order->post_by_date = $backMarket->date_shipping;
                        $Order->paypal_transaction_id = $backMarket->paypal_reference;
                        $Order->delivery_service = $backMarket->shipper;
                        $Order->tracking_number = $backMarket->tracking_number;
                        // $Order->account_id = $ebay->accountId;
                        //  $Order->billing_address3 = $ebay->billingAddress3;
                        $Order->billing_address_company_name = $backMarket->billing_address->company;
                        $Order->billing_address_country_code = $backMarket->billing_address->country;
                        $Order->billing_phone_number = $backMarket->billing_address->phone;
                        $Order->currency_code = $backMarket->currency;
                        // $Order->discount_description = '';
                        //  $Order->invoice_emailed_date = '';
                        // $Order->invoice_number = '';
                        // $Order->invoice_printed_date = $backMarket->currency;;
                        // $Order->reason = $backMarket->currency;;
                        //$Order->shipping_address3 = $backMarket->street2;
                        $Order->shipping_address_company_name = $backMarket->shipping_address->company;
                        $Order->shipping_address_country_code = $backMarket->shipping_address->country;
                        // $Order->shipping_alias = $backMarket->shipping_address->phone;
                        $Order->shipping_email = $backMarket->shipping_address->email;
                        $Order->shipping_method = $backMarket->shipper_display;
                        $Order->status = $status;
                        //$Order->tag = '';
                        //$Order->total_discount = $backMarket->totalDiscount;
                        $Order->platform = Stock::PLATFROM_BACKMARCKET;

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

                        foreach ($backMarket->orderlines as $item) {
                            $OrderItem = EbayOrderItems::firstOrNew([
                                'item_id' => $item->id,
                                'order_id' => $Order->id
                            ]);


                            $tax=0.00;
                            if(isset($item->listing)){
                                if(strpos($item->listing, 'STD') !== false) {
                                    $tax = 0.20;
                                }elseif (strpos($item->listing, 'MRG') !== false){
                                    $tax = 0.00;
                                }else{
                                    $tax=$item->sales_taxes;
                                }
                            }


                            $OrderItem->order_id = $Order->id;
                            $OrderItem->sales_record_number = $Order->sales_record_number;
                            $OrderItem->item_id = $item->id;
                          //  $OrderItem->external_id = $item->externalId;
                            $OrderItem->item_number = $item->id;
                            $OrderItem->item_name = $item->product;
                            $OrderItem->item_sku = $item->listing;
                            $OrderItem->quantity = $item->quantity;
                            $OrderItem->individual_item_price = $item->price;
                           // $OrderItem->individual_item_discount_price = $item->individualItemDiscountPrice;
                            $OrderItem->tax_percentage = $tax;
                            $OrderItem->condition=$item->condition;
//                            $OrderItem->giftwrap = json_encode($item->giftWrap);
//                            $OrderItem->weight = $item->weight;
//                            $OrderItem->item_image = count($item->images) > 0 ? $item->images[0]->url : '';

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
