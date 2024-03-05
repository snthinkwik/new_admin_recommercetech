<?php namespace App\Console\Commands\ebay;

use App\Models\AccessToken;
use App\Models\EbayImage;
use App\Models\EbayOrderLog;
use App\Models\EbayOrders;
use App\Models\HistoryLog;
use App\Models\EbayOrderItems;
use App\Models\Stock;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use GuzzleHttp;
use GuzzleHttp\Subscriber\Oauth\Oauth1;
use GuzzleHttp\Client;
class SyncAllOrderByEbayApi extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'ebay:api-sync-all-orders';

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


	    $accessToken=AccessToken::where('platform', 'ebay')->first();
        $currentTime = Carbon::now();
        $addTime=\Carbon\Carbon::parse($accessToken->updated_at)->addSecond($accessToken->expires_in);
        $BasicHeaders=ebayBasicToken(config('services.ebay.client_id'),config('services.ebay.client_secret'));
        $this->comment("Initialization....");
        $startDate = date('Y-m-d H:i:s');
        $i = 0;
        $d = 0;
        $k=0;

        do{
            if($currentTime->gt($addTime)){
                $this->comment("Access Token is Expired....");
                $this->comment("New Generated Access Token....");
                $newAccessToken= getEbayRefreshTokenBaseToken($BasicHeaders,$accessToken->refresh_token);
                $accessToken->access_token=$newAccessToken['access_token'];
                $accessToken->expires_in=$newAccessToken['expires_in'];
                $accessToken->save();
                sleep(1);
            }

            $headers = array(
                'Authorization:Bearer '.$accessToken->access_token,
            );



            $eBayApiResponse = getEbayOrderData($headers, $i);
            $j = $eBayApiResponse["total"];
            if(!$k){
                $eBayApiResponse = getEbayOrderData($headers, 0);
            }else{
                if(isset($eBayApiResponse['next'])){
                    $str = substr($eBayApiResponse['next'], strpos($eBayApiResponse['next'], 'offset'));
                    $offset=str_replace("offset=","",$str);
                    $i=$offset;
                    $eBayApiResponse = getEbayOrderData($headers, $offset);
                }
                else{
                    $i=$eBayApiResponse["total"]+1;
                    $eBayApiResponse = getEbayOrderData($headers, $eBayApiResponse['offset']);
                }

            }
            if(isset($eBayApiResponse['orders'])){

                foreach ($eBayApiResponse['orders'] as $ebay){
                    $Order = EbayOrders::firstOrNew([
                        'sales_record_number' => $ebay->salesRecordReference
                    ]);
                    $status='';
                    if(in_array($ebay->cancelStatus,['CANCELED','IN_PROGRESS'])){
                        $status=EbayOrders::STATUS_CANCELLED;
                    }elseif (in_array($ebay->orderFulfillmentStatus,['FULFILLED','NOT_STARTED'])){
                        if($ebay->orderFulfillmentStatus==="FULFILLED"){
                            $status=EbayOrders::STATUS_DISPATCHED;
                        }else{
                            $status=EbayOrders::STATUS_NEW;
                        }
                    }elseif (in_array($ebay->orderPaymentStatus,['FULLY_REFUNDED','PARTIALLY_REFUNDED','PENDING'])){
                        if($ebay->orderPaymentStatus==="FULLY_REFUNDED" ||$ebay->orderPaymentStatus==="PARTIALLY_REFUNDED"){
                            $status=EbayOrders::STATUS_REFUNDED;
                        }else{
                            $status=EbayOrders::STATUS_AWAITING_PAYMENT;
                        }
                    }
                    $countryName=getCountry($ebay->fulfillmentStartInstructions[0]->shippingStep->shipTo->contactAddress->countryCode);
                    $Order->sales_record_number=$ebay->salesRecordReference;
                    $Order->order_number=$ebay->orderId;
                    $Order->order_id=$ebay->orderId;
                    $Order->buyer_name = isset($ebay->fulfillmentStartInstructions[0]->shippingStep->shipTo->fullName)?$ebay->fulfillmentStartInstructions[0]->shippingStep->shipTo->fullName:'-';
                    $Order->buyer_email = isset($ebay->fulfillmentStartInstructions[0]->shippingStep->shipTo->email)?$ebay->fulfillmentStartInstructions[0]->shippingStep->shipTo->email:'-' ;
                    $Order->buyer_note ='' ;
                    $Order->buyer_address_1 =isset($ebay->fulfillmentStartInstructions[0]->shippingStep->shipTo->contactAddress->addressLine1)?$ebay->fulfillmentStartInstructions[0]->shippingStep->shipTo->contactAddress->addressLine1:'-';
                    $Order->buyer_address_2 = isset($ebay->fulfillmentStartInstructions[0]->shippingStep->shipTo->contactAddress->addressLine2)?$ebay->fulfillmentStartInstructions[0]->shippingStep->shipTo->contactAddress->addressLine2:'-';
                    $Order->buyer_city =isset($ebay->fulfillmentStartInstructions[0]->shippingStep->shipTo->contactAddress->city)?$ebay->fulfillmentStartInstructions[0]->shippingStep->shipTo->contactAddress->city:'-';
                    $Order->buyer_county = isset($ebay->fulfillmentStartInstructions[0]->shippingStep->shipTo->contactAddress->stateOrProvince)?$ebay->fulfillmentStartInstructions[0]->shippingStep->shipTo->contactAddress->stateOrProvince:'-';
                    $Order->buyer_postcode = isset($ebay->fulfillmentStartInstructions[0]->shippingStep->shipTo->contactAddress->postalCode)?$ebay->fulfillmentStartInstructions[0]->shippingStep->shipTo->contactAddress->postalCode:'-';
                    $Order->buyer_country = $countryName;
                    $Order->post_to_name = isset($ebay->fulfillmentStartInstructions[0]->shippingStep->shipTo->fullName)?$ebay->fulfillmentStartInstructions[0]->shippingStep->shipTo->fullName:'-' ;
                    $Order->post_to_phone = isset($ebay->fulfillmentStartInstructions[0]->shippingStep->shipTo->primaryPhone->phoneNumber)?$ebay->fulfillmentStartInstructions[0]->shippingStep->shipTo->primaryPhone->phoneNumber:'-';
                    $Order->post_to_address_1 = isset($ebay->fulfillmentStartInstructions[0]->shippingStep->shipTo->contactAddress->addressLine1)?$ebay->fulfillmentStartInstructions[0]->shippingStep->shipTo->contactAddress->addressLine1:'-';
                    $Order->post_to_address_2 = isset($ebay->fulfillmentStartInstructions[0]->shippingStep->shipTo->contactAddress->addressLine2)?$ebay->fulfillmentStartInstructions[0]->shippingStep->shipTo->contactAddress->addressLine2:'-';
                    $Order->post_to_city = isset($ebay->fulfillmentStartInstructions[0]->shippingStep->shipTo->contactAddress->city)?$ebay->fulfillmentStartInstructions[0]->shippingStep->shipTo->contactAddress->city:'-';
                    $Order->post_to_county = isset($ebay->fulfillmentStartInstructions[0]->shippingStep->shipTo->contactAddress->stateOrProvince)? $ebay->fulfillmentStartInstructions[0]->shippingStep->shipTo->contactAddress->stateOrProvince:'-';
                    $Order->post_to_postcode = isset($ebay->fulfillmentStartInstructions[0]->shippingStep->shipTo->contactAddress->postalCode)?$ebay->fulfillmentStartInstructions[0]->shippingStep->shipTo->contactAddress->postalCode:'-';
                    $Order->post_to_country = $countryName;
                    $Order->postage_and_packaging =$ebay->pricingSummary->deliveryCost->value;
                    $Order->total_price =$ebay->pricingSummary->priceSubtotal->value;
                    $Order->payment_method = '-';
                    $Order->sale_date = $ebay->creationDate;
                    $Order->paid_on_date = $ebay->creationDate;
                    $Order->post_by_date = '';
                    $Order->paypal_transaction_id ='';
                    $Order->delivery_service = isset($ebay->fulfillmentStartInstructions[0]->shippingStep->shippingServiceCode)?$ebay->fulfillmentStartInstructions[0]->shippingStep->shippingServiceCode:'-';
                    $Order->tracking_number = '';
                    $Order->account_id ='';
                    $Order->billing_address3 = '';
                    $Order->billing_address_company_name ='';
                    $Order->billing_address_country_code = isset($ebay->fulfillmentStartInstructions[0]->shippingStep->shipTo->contactAddress->countryCode)?$ebay->fulfillmentStartInstructions[0]->shippingStep->shipTo->contactAddress->countryCode:'-';
                    $Order->billing_phone_number = isset($ebay->fulfillmentStartInstructions[0]->shippingStep->shipTo->primaryPhone->phoneNumber)?$ebay->fulfillmentStartInstructions[0]->shippingStep->shipTo->primaryPhone->phoneNumber:'-';
                    $Order->currency_code = $ebay->pricingSummary->priceSubtotal->currency;
                    $Order->discount_description = '';
                    $Order->invoice_emailed_date = '';
                    $Order->invoice_number = '';
                    $Order->invoice_printed_date ='';
                    $Order->reason = '';
                    $Order->shipping_address3 = '';
                    $Order->shipping_address_company_name = '';
                    $Order->shipping_address_country_code = $ebay->pricingSummary->priceSubtotal->currency;
                    $Order->shipping_alias ='';
                    $Order->shipping_email = isset($ebay->fulfillmentStartInstructions[0]->shippingStep->shipTo->email)?$ebay->fulfillmentStartInstructions[0]->shippingStep->shipTo->email:'';
                    $Order->shipping_method = isset($ebay->fulfillmentStartInstructions[0]->shippingStep->shippingServiceCode)?$ebay->fulfillmentStartInstructions[0]->shippingStep->shippingServiceCode:'-';
                    $Order->status = $status;
                    $Order->tag ='';
                    $Order->total_discount = isset($ebay->pricingSummary->priceDiscount->value)?$ebay->pricingSummary->priceDiscount->value:0;
                    $Order->platform =Stock::PLATFROM_EBAY;
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
                    foreach ($ebay->lineItems as $item) {
                        $id=explode('-',$ebay->legacyOrderId);
                        $ebayImage=EbayImage::where('items_id',$id[0])->first();
                        $imagePath=Null;
                        if(!is_null($ebayImage)){
                            $imagePath=$ebayImage->image_path;
                        }
                        $tax=0.00;
                        if(isset($item->sku)){
                            if(strpos($item->sku, 'STD') !== false) {
                                $tax = 0.20;
                            }
                        }
                        $OrderItem = EbayOrderItems::firstOrNew([
                            'item_id' => $item->lineItemId,
                            'order_id' => $Order->id
                        ]);


                        $OrderItem->order_id = $Order->id;
                        $OrderItem->sales_record_number = $ebay->salesRecordReference;
                        $OrderItem->item_id = $item->lineItemId;
                        $OrderItem->external_id = $ebay->legacyOrderId;
                        $OrderItem->item_number = $id[0];
                        $OrderItem->item_name = $item->title;
                        $OrderItem->item_sku = isset($item->sku)?$item->sku:'' ;
                        $OrderItem->quantity = $item->quantity;
                        $discount=count($item->appliedPromotions)>0 ? $item->appliedPromotions[0]->discountAmount->value:'0';
                        $OrderItem->individual_item_price = ($item->lineItemCost->value-$discount)/$item->quantity;
                        $OrderItem->individual_item_discount_price =$discount;
                        $OrderItem->tax_percentage = $tax;
                        $OrderItem->item_image =!is_null($imagePath)?$imagePath:$OrderItem->item_image;
                        $OrderItem->ship_by_date=isset($item->lineItemFulfillmentInstructions->shipByDate)?$item->lineItemFulfillmentInstructions->shipByDate:'';


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
            $k++;

        }while ($j >= $i);
        $endDate = date('Y-m-d H:i:s');
        $historyLogModel = new HistoryLog();
        $historyLogModel->script_started = $startDate;
        $historyLogModel->script_finished = $endDate;
        $historyLogModel->import_count = $d;
        $historyLogModel->save();

	}

}
