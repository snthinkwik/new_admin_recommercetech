<?php

namespace App\Jobs\Dpd;

use App\Models\AccessToken;
use App\Models\Invoice;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\Stock;
use App\Models\TrackingBackMarketDPDShipping;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreateShipping implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $data;
    protected $saleId;
    protected $ebayOrder;
    public function __construct($data,$saleId,$ebayOrder)
    {
        $this->data = $data;
        $this->saleId = $saleId;
        $this->ebayOrder=$ebayOrder;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $settings=Setting::where('key','dpd_shipping_token')->first();
        $header = array(
            "Content-Type:application/json",
            "Accept: application/json",
            "GEOClient: account/3000860",
            "GeoSession:".$settings->value,
        );

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.dpdlocal.co.uk/shipping/shipment",
            CURLOPT_HTTPHEADER => $header,
            // CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($this->data)
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        $sale=Sale::find($this->saleId);
        $sale->shipment_id=json_decode($response)->data->shipmentId;
        $sale->parcel_numbers=json_decode($response)->data->consignmentDetail[0]->parcelNumbers[0];
        $sale->consignment_number=json_decode($response)->data->consignmentDetail[0]->consignmentNumber;
        $sale->save();

        foreach ($this->ebayOrder->EbayOrderItems as $item){
            if($sale->platform===Stock::PLATFROM_BACKMARCKET){
                $data=  [
                    'sku'=>$item->item_sku,
                    "new_state"=>2,
                    "order_id"=>$this->ebayOrder->order_id,
                ];


                $header = array(
                    "Authorization:Basic ".config('services.back_market.token'),
                );
                $ua = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/525.13 (KHTML, like Gecko) Chrome/0.A.B.C Safari/525.13';

                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => "https://www.backmarket.fr/ws/orders/".$this->ebayOrder->order_id,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
                    CURLOPT_HTTPHEADER => $header,
                    CURLOPT_USERAGENT=>$ua,
                    CURLOPT_POSTFIELDS => $data
                ));

                $result = curl_exec($curl);

                if (!$result) {
                    die("Connection Failure");
                }
                $data = (array) json_decode($result);


            }elseif($sale->platform===Stock::PLATFROM_EBAY)
            {

                $accessToken=AccessToken::where('platform', 'ebay')->first();
                $headers = array(
                    'Authorization:Bearer '.$accessToken->access_token,
                    "X-EBAY-C-MARKETPLACE-ID",
                    "Content-Type:application/json"
                );
                $data  =  [
                    "lineItems"=>[
                        [
                            "lineItemId"=> $item->item_id,
                            "quantity"=> $item->quantity
                        ]

                    ],
                    "shippedDate"=> $item->ship_by_date,
                    "shippingCarrierCode"=> "DPD",
                    "trackingNumber"=> json_decode($response)->data->consignmentDetail[0]->consignmentNumber
                ];





                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => "https://api.ebay.com/sell/fulfillment/v1/order/".$this->ebayOrder->order_id."/shipping_fulfillment",
                    CURLOPT_HTTPHEADER => $headers,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => json_encode($data)
                ));
                $ebayResponse = curl_exec($curl);
                curl_close($curl);

            }



            $trackingBackMarket = new TrackingBackMarketDPDShipping();
            $trackingBackMarket->sales_id=$this->saleId;
            $trackingBackMarket->tracking_number=json_decode($response)->data->consignmentDetail[0]->consignmentNumber;
            $trackingBackMarket->platfrom=$sale->platform;
            $trackingBackMarket->order_id=$this->ebayOrder->order_id;
            $trackingBackMarket->sku=$item->item_sku;
            if(!is_null($item->stock)){
                $trackingBackMarket->imei= $item->stock->imei!=="" ?$item->stock->imei:$item->stock->serial;
            }
            $trackingBackMarket->save();
        }

        $sale=Sale::find($this->saleId);

        $sale->shipment_id=json_decode($response)->data->shipmentId;
        $sale->parcel_numbers=json_decode($response)->data->consignmentDetail[0]->parcelNumbers[0];
        $sale->consignment_number=json_decode($response)->data->consignmentDetail[0]->consignmentNumber;
        $sale->invoice_status=Invoice::STATUS_DISPATCHED;
        $sale->tracking_number=json_decode($response)->data->consignmentDetail[0]->consignmentNumber;
        $sale->courier="DPD";

        $sale->save();

    }

}
