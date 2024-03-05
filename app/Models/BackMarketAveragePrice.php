<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class BackMarketAveragePrice extends Model
{
    use HasFactory;

    protected $table = 'back_market_average_price';
    protected $fillable = ['back_market_product_id', 'condition', 'price', 'price_for_buybox', 'category', 'product_name', 'ean', 'model', 'mpn', 'product_id'];


    public function scopeFromRequest(Builder $query, Request $request)
    {

        if ($request->sort) {
            $query->orderBy($request->sort, $request->sortO);
        }
        if ($request->brand) {

            $query->where('make', $request->brand);
        }
        if ($request->product_id) {
            $query->where('product_id', $request->product_id);
        }
        if ($request->condition) {
            $query->where('condition', $request->condition);
        }
        if ($request->buy_box) {

            if ($request->buy_box === "yes") {
                $query->where('same_merchant_winner', 1);
            } else {
                $query->where('same_merchant_winner', 0);
            }

        }
        if ($request->filter) {
            $query->where('product_name', 'like', "%" . $request->filter . "%");
            $query->orWhere('ean', 'like', "%" . $request->filter . "%");
            $query->orWhere('mpn', 'like', "%" . $request->filter . "%");
            $query->orWhere('model', 'like', "%" . $request->filter . "%");
        }
        return $query;
    }

    public function maxPrice()
    {

        return $this->hasOne(BackMarketMaxPrice::class, 'back_market_product_id', 'back_market_product_id');
    }

}
