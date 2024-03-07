<?php

namespace App\Jobs\ebay;

use App\Jobs\Dpd\CreateShipping;
use App\Jobs\Sales\EmailSend;
use App\Models\DeliveryNotes;
use App\Models\DeliverySettings;
use App\Models\Invoice;
use App\Models\EbayOrders;
use App\Models\Sale;
use App\Models\User;
use App\Models\Product;
use App\Models\SellerFees;
use App\Models\Setting;
use App\Models\Stock;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreateNewInvoice implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */

    /**
     * Create a new command instance.
     *
     * @return void
     */

    use InteractsWithQueue, SerializesModels;

    /**
     * @var Sale;
     */
    protected $sale;

    /**
     * @var User
     */
    protected $customer;

    /**
     * @var string|null One of Invoicing::DELIVERY_* constants.
     */
    protected $deliveryName;

    /**
     * @var string One of Invoicing::SALE_* constants.
     */
    protected $saleName;

    /**
     * @var Batch
     */
    protected $batch;
    /**
     * @var Price
     */
    protected $price;

    /**
     * @var Auction
     */
    protected $auction;

    protected $fee;
    protected  $trackingNumber;
    protected $ebay_order;
    public function __construct(Sale $sale,EbayOrders $ebayOrder ,$customer, $saleName, $deliveryName = null,$trackingNumber=null, $batch = null, $price = null, Auction $auction = null, $fee = null)
    {
        $this->sale = $sale;
        $this->ebay_order=$ebayOrder;
        $this->customer = $customer;
        $this->deliveryName = $deliveryName;
        $this->saleName = $saleName;
        $this->batch = $batch;
        $this->price = $price;
        $this->auction = $auction;
        $this->fee = $fee;
        $this->trackingNumber=$trackingNumber;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $dataJson=[];
        $setting=null;
        try {

            $invoice_details = [
                'type' => 'InvoiceCreate',
                'sale' => $this->sale->id,
                'customerUser' => $this->customer->id,
                'saleName' => $this->saleName,
                'deliveryName' => $this->deliveryName,
                'batch' => $this->batch,
                'price' => $this->price,
                'auction' => $this->auction ? $this->auction->id : null,
            ];
            $this->sale->invoice_details = $invoice_details;
            $this->sale->save();
        } catch (Exception $e) {

            print($e);
        }

        try {


            $this->sale->invoice_creation_status = Invoice::CREATION_STATUS_IN_PROGRESS;
            $this->sale->save();


            if($this->batch!=null && $this->price!=null)
                $result = app('App\Contracts\Invoicing')->createBatchInvoice($this->sale, $this->customer, $this->saleName, $this->deliveryName, $this->batch, $this->price, $this->fee);
            else
                $result = app('App\Contracts\Invoicing')->createBayInvoice($this->sale,$this->ebay_order ,$this->customer, $this->saleName, $this->deliveryName, $this->fee,$this->trackingNumber);

            $this->sale->invoice_api_id = $result['id'];
            $this->sale->invoice_number = $result['invoice_no'];
            $this->sale->invoice_total_amount = $result['amount'];
            $this->sale->invoice_creation_status = Invoice::CREATION_STATUS_SUCCESS;
            $this->sale->invoice_description = 'Invoice created';
            $this->sale->invoice_doc_number =   $this->sale->id;
            $this->sale->delivery_charges=$result['delivery_charges'];
            $this->sale->total_cost=isset($result['total_cost'])?$result['total_cost']:null;
            $this->sale->total_sale_price=isset($result['total_sales'])?$result['total_sales']:null;
            $this->sale->total_purchase_price=isset($result['total_purchase'])?$result['total_purchase']:null;
            $this->sale->vat_type=isset($result['vat_type'])?$result['vat_type']:null;



            $fee=$result['amount'];


            $masterEbay= EbayOrders::find($result['ebay_order_id']);
            if($result['platform'] === Stock::PLATFROM_RECOMM){

                if(!is_null( $masterEbay)){
                    $masterEbay->sales_record_number=$this->sale->id.'-'.$result['number'];
                    $masterEbay->save();
                }
            }




            $this->sale->platform=$result['platform'];;
            if($result['platform']===Stock::PLATFROM_MOBILE_ADVANTAGE){



                if($result['payment_methods']==="KLARNA"){

                    if($result['payment_type']!==""){
                        if($result['payment_type']==="pay_by_card"){

                            $totalSellingCoast=getTotalSellingCost('MobileAdvantage - Klarna Upfront by Card',$fee,$result['ebay_country']);
                            $fees=SellerFees::where('platform','MobileAdvantage - Klarna Upfront by Card')->first();

                        }else if ($result['payment_type']==="pay_later_by_card"){

                            $totalSellingCoast=getTotalSellingCost('MobileAdvantage - Klarna (Pay in Parts)',$fee,$result['ebay_country']);
                            $fees=SellerFees::where('platform','MobileAdvantage - Klarna (Pay in Parts)')->first();

                        }elseif($result['payment_type']==="slice_it_by_card"){
                            $totalSellingCoast=getTotalSellingCost('MobileAdvantage -  Klarna (Slice it Financing)',$fee,$result['ebay_country']);
                            $fees=SellerFees::where('platform','MobileAdvantage -  Klarna (Slice it Financing)')->first();
                        }else{
                            $totalSellingCoast=getTotalSellingCost('MobileAdvantage - Klarna Upfront by Card',$fee,$result['ebay_country']);
                            $fees=SellerFees::where('platform','MobileAdvantage - Klarna Upfront by Card')->first();
                        }

                    }else{
                        $totalSellingCoast=getTotalSellingCost('MobileAdvantage - Klarna Upfront by Card',$fee,$result['ebay_country']);
                        $fees=SellerFees::where('platform','MobileAdvantage - Klarna Upfront by Card')->first();
                    }

                }elseif ($result['payment_methods']==="STRIPE"){

                    $totalSellingCoast=getTotalSellingCost('MobileAdvantage - Stripe Upfront',$fee,$result['ebay_country']);
                    $fees=SellerFees::where('platform','MobileAdvantage - Stripe Upfront')->first();
                }else{

                    $totalSellingCoast=getTotalSellingCost('MobileAdvantage - Klarna Upfront by Card',$fee,$result['ebay_country']);
                    $fees=SellerFees::where('platform','MobileAdvantage - Klarna Upfront by Card')->first();

                }



            }else{
                $totalSellingCoast=getTotalSellingCost($result['platform'],$fee,$result['ebay_country']);
                $fees=SellerFees::where('platform',$result['platform'])->first();
            }




            if(!is_null($fees->warranty_accrual)){
                $warranty_accrual=$fee* $fees->warranty_accrual/100;
            }

            $this->sale->platform_fee=$fees->platform_fees > 0 ?  ($fee* $fees->platform_fees/100)+$fees->accessories_cost_ex_vat+$warranty_accrual:0+$fees->accessories_cost_ex_vat+$warranty_accrual;
            //   $this->sale->platform_fee=$fees->platform_fees > 0 ?  ($fee* $fees->platform_fees/100)+$fees->accessories_cost_ex_vat:0+$fees->accessories_cost_ex_vat;
            $this->sale->shipping_cost=$totalSellingCoast;


            if(count($result['product_ids'])>0){
                foreach ($result['product_ids'] as $key=>$value){
                    $id=explode('-',$key);
                    $product=Product::find($id[1]);
                    $product->multi_quantity= ($product->multi_quantity-$value);
                    $product->save();

                }
            }
            if($this->fee) {
                $this->sale->card_processing_fee = true;
            }

            $this->sale->save();


            $deliveryNote = DeliveryNotes::firstOrNew([
                'sales_id' => $this->sale->id
            ]);

            $deliveryNote->date=$result['create_at'];
            $deliveryNote->item_list=json_encode($result['item_list']);
            $deliveryNote->invoice_number= $result['invoice_no'];
            $deliveryNote->save();



            if(count($this->sale->stock)<10){
                $deliverySettings=DeliverySettings::where('service_name','Express Pack')->first();


                // Your code here!
                $array = explode(' ', $masterEbay->buyer_postcode);


                if(strpos($array[0], 'BT') !== false)
                {
                    $networkCode="2^11";
                }elseif(strpos($array[0], 'HS') !== false)
                {
                    $networkCode="2^11";
                }elseif (strpos($array[0], 'PA') !== false)
                {
                    $networkCode="2^11";
                }elseif (strpos($array[0], 'IV') !== false)
                {
                    $networkCode="2^11";
                }elseif (strpos($array[0], 'GY') !== false)
                {
                    $networkCode="2^11";
                }elseif (strpos($array[0], 'IM') !== false)
                {
                    $networkCode="2^11";
                }elseif (strpos($array[0], 'PO30') !== false) {
                    $networkCode = "2^11";
                }elseif ($masterEbay->post_to_country==="Republic of Ireland")
                {
                    $networkCode = "2^11";
                }
                else
                {
                    $networkCode="2^32";
                }
            }else{
                $deliverySettings=DeliverySettings::where('service_name','Parcel up to 10KG')->first();

                $array = explode(' ', $masterEbay->buyer_postcode);
//                $masterEbay->post_to_country

                if(strpos($array[0], 'BT') !== false)
                {
                    $networkCode="2^11";
                }elseif(strpos($array[0], 'HS') !== false)
                {
                    $networkCode="2^11";
                }elseif (strpos($array[0], 'PA') !== false)
                {
                    $networkCode="2^11";
                }elseif (strpos($array[0], 'IV') !== false)
                {
                    $networkCode="2^11";
                }elseif (strpos($array[0], 'GY') !== false)
                {
                    $networkCode="2^11";
                }elseif (strpos($array[0], 'IM') !== false)
                {
                    $networkCode="2^11";
                }elseif (strpos($array[0], 'PO30') !== false)
                {
                    $networkCode = "2^11";
                }elseif ($masterEbay->post_to_country==="Republic of Ireland")
                {
                    $networkCode = "2^11";
                }
                else
                {
                    $networkCode="2^1";
                }



            }

            if(!is_null($masterEbay))
            {

                $email='';
                if(in_array($result['platform'],[Stock::PLATFROM_MOBILE_ADVANTAGE,Stock::PLATFROM_RECOMM])){
                    $email= $masterEbay->buyer_email;
                }


                $dataJson = [
                    "jobId"=>null,
                    "collectionOnDelivery"=> false,
                    "generateCustomsData"=>"Y",
                    "invoice"=> [
                        "invoiceExportReason"=> "01",
                        "invoiceType"=> 1,
                        "shippingCost"=> $deliverySettings->cost,
                        "invoiceShipperDetails"=> [
                            "contactDetails" => [
                                "contactName" =>$masterEbay->buyer_name,
                                "telephone"=> $masterEbay->billing_phone_number,
                            ],
                            "address"=> [
                                "organisation"=> $masterEbay->billing_address_company_name,
                                "countryCode"=> getCountryCode($masterEbay->buyer_country),
                                "postcode"=> $masterEbay->buyer_postcode,
                                "street"=> $masterEbay->buyer_address_1,
                                "locality"=> $masterEbay->buyer_address_2,
                                "town"=> $masterEbay->buyer_city,
                                "county"=> $masterEbay->buyer_county
                            ],

                            "eoriNumber"=> ""
                        ],
                        "invoiceDeliveryDetails"=> [
                            "contactDetails"=> [
                                "contactName" =>$masterEbay->buyer_name,
                                "telephone"=> $masterEbay->billing_phone_number,
                                "email"=> $masterEbay->buyer_email,

                            ],
                            "address"=> [
                                "organisation"=> $masterEbay->billing_address_company_name,
                                "countryCode"=> getCountryCode($masterEbay->buyer_country),
                                "postcode"=> $masterEbay->buyer_postcode,
                                "street"=> $masterEbay->buyer_address_1,
                                "locality"=> $masterEbay->buyer_address_2,
                                "town"=> $masterEbay->buyer_city,
                                "county"=> $masterEbay->buyer_county
                            ],

                            "eoriNumber"=> "",
                        ]
                    ],

                    "collectionDate"=> Carbon::now(),
                    "consolidate"=> false,
                    "consignment"=> [
                        [
                            "consignmentNumber"=> null,
                            "consignmentRef"=> null,
                            "parcel"=> [
                                [
                                    "packageNumber"=> 1,
                                    "parcelProduct"=> [[]]
                                ]
                            ],
                            "collectionDetails"=> [
                                "contactDetails"=> [
                                    "contactName" =>'CHRIS EATON',
                                    "telephone"=>'02030111040',
                                ],
                                "address"=> [
                                    "organisation"=> 'RECOMMERCE LTD',
                                    "countryCode"=> 'GB',
                                    "postcode"=> 'HP123RL',
                                    "street"=> '49, CRESSEX ENTERPRISE CENTRE, LINC',
                                    "locality"=> "CRESSEX BUSINESS PARK",
                                    "town"=> 'HIGH WYCOMBE',
                                    "county"=> 'BUCKINGHAMSHIRE'
                                ]
                            ],
                            "deliveryDetails"=> [
                                "contactDetails"=> [
                                    "contactName" =>$masterEbay->post_to_name,
                                    "telephone"=> $masterEbay->post_to_phone,
                                ],
                                "address"=> [
                                    "organisation"=> $masterEbay->post_to_name,
                                    "countryCode"=> getCountryCode($masterEbay->post_to_country),
                                    "postcode"=> $masterEbay->post_to_postcode,
                                    "street"=> $masterEbay->post_to_address_1,
                                    "locality"=> $masterEbay->post_to_address_2,
                                    "town"=> $masterEbay->post_to_city,
                                    "county"=> $masterEbay->post_to_county
                                ],
                                "notificationDetails"=> [
                                    'name'=>$masterEbay->buyer_name,
                                    "email" =>$email,
                                    "mobile"=> $masterEbay->billing_phone_number,
                                ]
                            ],
                            "networkCode"=> $networkCode,
                            "numberOfParcels"=> 1,
                            "totalWeight"=> 1,
                            "customsCurrency"=> "GBP",
                            "deliveryInstructions"=> "",
                            "parcelDescription"=> "",
                            "vatPaid"=> "N"
                        ]
                    ]
                ];
                $setting=Setting::where('key','dpd_shipping_status')->first();
//                if($setting->value){
//                    if($result['platform']===Stock::PLATFROM_BACKMARCKET || $result['platform']===Stock::PLATFROM_EBAY || $result['platform']===Stock::PLATFROM_MOBILE_ADVANTAGE){
//                        Queue::pushOn(
//                            'dpd-shipping',
//                            new CreateShipping($dataJson,$this->sale->id,$this->ebay_order)
//                        );
//                    }
//
//                }
            }




            if($result['platform']===Stock::PLATFROM_MOBILE_ADVANTAGE)
            {
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

                foreach ($this->ebay_order->EbayOrderItems as $item){

                    $stockimei=[];
                    if($item->quantity>1){
                        foreach (json_decode($item->stock_id) as $stockId){

                            $stock=Stock::find($stockId);
                            if($stock->imei!==""){
                                array_push($stockimei,$stock->imei);
                            }else{
                                array_push($stockimei,$stock->serial);
                            }
                        }

                        $data=[
                            'itemId'=>$item->item_id,
                            'imeiNumber'=>  implode(",", $stockimei),
                        ];

                        $response=updateIMEINumberMobileAdvantage($header,$data);
                    }else{
                        $stock=Stock::find($item->stock_id);
                        $data=[
                            'itemId'=>$item->item_id,
                            'imeiNumber'=>$stock->imei!=="" ? $stock->imei:$stock->serial,
                        ];
                        $response=updateIMEINumberMobileAdvantage($header,$data);
                    }
                }
            }



            //  app('App\Contracts\Invoicing')->markInvoicePaid($this->sale);


            $inoicing= app('App\Contracts\Invoicing');
            $customer = $inoicing->getCustomer($this->sale->customer_api_id);
            $invoicePath =$inoicing->getInvoiceDocument($this->sale);
            $newCustomer=[];
            if(!is_null($customer)){
                $newCustomer=[
                    'first_name'=>$customer->first_name,
                    'last_name'=>$customer->last_name,
                    'email'=>$customer->email,
                    'external_id'=>$customer->external_id,
                    'phone'=>isset($customer->phone)?$customer->phone:null,
                ];
            }



            if($this->auction!=null){
                dispatch(new EmailSend($this->sale,EmailSend::EMAIL_CREATED,$newCustomer,$invoicePath));
               // Queue::pushOn('emails', new EmailSend($this->sale, EmailSend::EMAIL_CREATED, $this->auction));
            } else {
               dispatch(new EmailSend($this->sale,EmailSend::EMAIL_CREATED,$newCustomer,$invoicePath));
              //  Queue::pushOn('emails', new EmailSend($this->sale, EmailSend::EMAIL_CREATED));
            }
        }
        catch (Exception $e) {
            $this->sale->invoice_creation_status = Invoice::CREATION_STATUS_ERROR;
            $this->sale->invoice_description = $e;
            $this->sale->save();

            if ($this->job) {
                $this->job->delete();
            }

            return false;
        }



        if($setting->value){
            if($result['platform']===Stock::PLATFROM_BACKMARCKET || $result['platform']===Stock::PLATFROM_EBAY || $result['platform']===Stock::PLATFROM_MOBILE_ADVANTAGE){
                dispatch(new CreateShipping($dataJson,$this->sale->id,$this->ebay_order));

            }

        }
    }

}
