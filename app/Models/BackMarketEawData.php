<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BackMarketEawData extends Model
{
    use HasFactory;
    protected $table="back_market_raw_data";

    protected $fillable=['product_id','sku','quantity','price','price_for_buybox','condition','same_merchant_winner','buybox','ean'];
}
