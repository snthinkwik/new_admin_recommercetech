<?php

namespace App\Models;

use App\Models\ProductImage;
use App\Models\Stock;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    public $timestamps = false;

    public function getImageAttribute()
    {
        return $this->image_path ? : $this->manual_image_path;
    }

    public function stock()
    {
        return $this->hasMany(Stock::class, 'product_id', 'id');
    }
    public function images()
    {
        return $this->hasMany(ProductImage::class,'product_id','id');
    }
}
