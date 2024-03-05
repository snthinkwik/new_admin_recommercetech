<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrackingBackMarketDPDShipping extends Model
{
    use HasFactory;
    protected $table="tracking_back_market_dpd_shipping";
    protected $fillable=['sales_id','status','tracking_number','platfrom','order_id','sku','imei'];
}
