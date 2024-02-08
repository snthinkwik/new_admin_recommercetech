<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EbayOrderSoldDetails extends Model
{
    use HasFactory;
    protected $table='ebay_sold_details';
    protected $fillable=['average_price_id','mpn','condition','user_name','sold_no'];
}
