<?php

namespace App\Jobs\Sales;
use App\Models\DeliveryNotes;
use App\Models\DeliverySettings;
use App\Models\Invoice;
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
use \App\Jobs\Dpd\CreateShippingRecommOrder;
use App\Jobs\Sales\EmailSend;

class InvoiceCreate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */

    /**
     * @var \App\Models\Sale;
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
    protected $platform;
    public function __construct(Sale $sale, $customer, $saleName,$platform ,$deliveryName = null, $batch = null, $price = null, Auction $auction = null, $fee = null)
    {
        $this->sale = $sale;
        $this->customer = $customer;
        $this->deliveryName = $deliveryName;
        $this->saleName = $saleName;
        $this->batch = $batch;
        $this->price = $price;
        $this->auction = $auction;
        $this->fee = $fee;
        $this->platform=$platform;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
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
                $result = app('App\Contracts\Invoicing')->createInvoice($this->sale, $this->customer, $this->saleName,$this->platform, $this->deliveryName, $this->fee);

            $this->sale->invoice_api_id = $result['id'];
            $this->sale->invoice_number = $result['invoice_no'];
            $this->sale->invoice_total_amount = $result['amount'];
            $this->sale->invoice_creation_status = Invoice::CREATION_STATUS_SUCCESS;
            $this->sale->invoice_description = 'Invoice created';
            $this->sale->invoice_doc_number = $this->sale->id;
            $this->sale->delivery_charges=$result['delivery_charges'];
            $this->sale->total_cost=isset($result['total_cost'])?$result['total_cost']:null;
            $this->sale->total_sale_price=isset($result['total_sales'])?$result['total_sales']:null;
            $this->sale->total_purchase_price=isset($result['total_purchase'])?$result['total_purchase']:null;
            $this->sale->vat_type=isset($result['vat_type'])?$result['vat_type']:null;

            $fee=$result['amount'];

            $totalSellingCoast=getTotalSellingCost($this->sale->platform,$fee,$result['country']);
            $fees=SellerFees::where('platform',$this->sale->platform)->first();

            $warranty_accrual=0;
            if(!is_null($fees->warranty_accrual)){
                $warranty_accrual=$fee* $fees->warranty_accrual/100;
            }




            $this->sale->platform_fee=$fees->platform_fees > 0 ?  ($fee* $fees->platform_fees/100)+$fees->accessories_cost_ex_vat+$warranty_accrual:0+$fees->accessories_cost_ex_vat+$warranty_accrual;
            $this->sale->shipping_cost=$totalSellingCoast;


            if(count($result['product_ids'])>0){
                foreach ($result['product_ids'] as $key=>$ids){
                    $product=Product::find($ids);
                    $stock=Stock::find($key);

                    $product->multi_quantity= ($product->multi_quantity-$stock->temporary_qty);
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

            $deliveryNote->sales_id=$this->sale->id;
            $deliveryNote->billing_address=json_encode($result['billing_address']);
            $deliveryNote->shipping_address=json_encode($result['shipping_address']);
            $deliveryNote->date=$result['create_at'];
            $deliveryNote->item_list=json_encode($result['item_list']);
            $deliveryNote->invoice_number= $result['invoice_no'];

            $deliveryNote->customer_name=$this->customer->first_name.' '.$this->customer->last_name;
            $deliveryNote->company_name=$result['company_name'];
            $deliveryNote->save();

            if(count($this->sale->stock)<10){
                $deliverySettings=DeliverySettings::where('service_name','Express Pack')->first();
            }else{
                $deliverySettings=DeliverySettings::where('service_name','Parcel up to 10KG')->first();
            }


            $dataJson=[
                "jobId"=>null,
                "collectionOnDelivery"=> false,
                "generateCustomsData"=>"Y",
                "invoice"=> [
                    "invoiceExportReason"=> "01",
                    "invoiceType"=> 1,
                    "shippingCost"=> $deliverySettings->cost,
                    "invoiceShipperDetails"=> [
                        "contactDetails" => [
                            "contactName" =>$this->customer->first_name.' '.$this->customer->last_name ,
                            "telephone"=> $this->customer->phone,
                        ],
                        "address"=> [
                            "organisation"=> $this->customer->company_name,
                            "countryCode"=> getCountryCode($result['billing_address']['country']),
                            "postcode"=> $result['billing_address']['postcode'],
                            "street"=> $result['billing_address']['line1'],
                            "locality"=> $result['billing_address']['line2'],
                            "town"=> $result['billing_address']['city'],
                            "county"=> $result['billing_address']['county']
                        ],

                        "eoriNumber"=> "GB123456789012"
                    ],
                    "invoiceDeliveryDetails"=> [
                        "contactDetails"=> [
                            "contactName" =>$this->customer->first_name.' '.$this->customer->last_name ,
                            "telephone"=> $this->customer->phone,
                            "email"=> $this->customer->email,

                        ],
                        "address"=> [
                            "organisation"=> $this->customer->company_name,
                            "countryCode"=> getCountryCode($result['billing_address']['country']),
                            "postcode"=> $result['billing_address']['postcode'],
                            "street"=> $result['billing_address']['line1'],
                            "locality"=> $result['billing_address']['line2'],
                            "town"=> $result['billing_address']['city'],
                            "county"=> $result['billing_address']['county']
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
                                "contactName" =>$this->customer->first_name.' '.$this->customer->last_name ,
                                "telephone"=> $this->customer->phone,
                            ],
                            "address"=> [
                                "organisation"=> $this->customer->company_name,
                                "countryCode"=> getCountryCode($result['shipping_address']['country']),
                                "postcode"=> $result['shipping_address']['postcode'],
                                "street"=> $result['shipping_address']['line1'],
                                "locality"=> $result['shipping_address']['line2'],
                                "town"=> $result['shipping_address']['city'],
                                "county"=> $result['shipping_address']['county']
                            ],
                            "notificationDetails"=> [
                                "telephone"=> $this->customer->phone,
                                "email"=> $this->customer->email,
                            ]
                        ],
                        "networkCode"=> "2^32",
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
            if($setting->value){
//                Queue::pushOn(
//                    'recomme-shipping',
//                    new CreateShippingRecommOrder($dataJson,$this->sale->id)
//                );
                dispatch( new CreateShippingRecommOrder($dataJson,$this->sale->id));
            }

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
               // Queue::pushOn('emails', new EmailSend($this->sale, EmailSend::EMAIL_CREATED, $this->auction));
                dispatch(new EmailSend($this->sale,EmailSend::EMAIL_CREATED,$newCustomer,$invoicePath));
            } else {
               // Queue::pushOn('emails', new EmailSend($this->sale, EmailSend::EMAIL_CREATED));
                dispatch(new EmailSend($this->sale,EmailSend::EMAIL_CREATED,$newCustomer,$invoicePath));
            }
        }
        catch (Exception $e) {
            $this->sale->invoice_creation_status = Invoice::CREATION_STATUS_ERROR;
            $this->sale->invoice_description = $e;
            $this->sale->save();

            if ($this->job) {
                $this->job->delete();
            }
        }
    }

}
