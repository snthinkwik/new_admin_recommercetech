<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BackMarketMaxPrice extends Model
{
    use HasFactory;
    protected $table='back_market_max_price';
    protected $fillable=['back_market_product_id','max_price'];
}
