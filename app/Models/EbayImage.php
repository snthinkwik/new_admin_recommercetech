<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EbayImage extends Model
{
    use HasFactory;
    protected $table="ebay_product";
    protected $fillable=['items_id','image_path','ean','mpn','epid'];
}
