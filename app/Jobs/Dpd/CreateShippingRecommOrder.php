<?php

namespace App\Jobs\Dpd;

use App\Models\Invoice;
use App\Models\Sale;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreateShippingRecommOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $data;
    protected $saleId;
    protected $items;
    public function __construct($data,$saleId)
    {
        $this->data = $data;
        $this->saleId = $saleId;
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
        $sale->invoice_status=Invoice::STATUS_DISPATCHED;
        $sale->tracking_number=json_decode($response)->data->consignmentDetail[0]->consignmentNumber;
        $sale->courier="DPD";
        $sale->save();
    }
}
