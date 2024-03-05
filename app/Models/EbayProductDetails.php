<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EbayProductDetails extends Model
{
    use HasFactory;
    protected $table='ebay_product_details';

    protected $fillable=['item_id','mpn','condition','user_name','product_link'];
}
