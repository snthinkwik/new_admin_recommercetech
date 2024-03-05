<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BackMarketProduct extends Model
{
    use HasFactory;
    protected $table="back_market_product";
    protected $fillable=['back_market_id','product_id','title','ean','brand','state','category_name','weight','height','depth','width'];

}
