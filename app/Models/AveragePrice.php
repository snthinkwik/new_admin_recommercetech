<?php

namespace App\Models;

use App\EbayOrderSoldDetails;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AveragePrice extends Model
{
    use HasFactory;
    protected $table='average_price';
    protected $fillable=['product_name',
        'ean',
        'mpn',
        'epid',
        'condition',
        'seller_wjd',
        'seller_ioutlet',
        'seller_secondhand_mobiles',
        'best_seller_price_on_marketplace',
        'best_seller_name',
        'platform',
        'make',
        'product_id'
    ];



    public function scopeFromRequest(Builder $query, Request $request) {

        if ($request->sort) {
            $query->orderBy($request->sort, $request->sortO);
        }
        if($request->platform){
            $query->where('platform',$request->platform);
        }
        if($request->condition){
            $query->where('condition',$request->condition);
        }
        if($request->filter){



            $query->where(DB::raw('concat(make," ",product_name)') , 'LIKE' , '%'.$request->filter.'%');
            $query->orWhere('mpn','like',"%".$request->filter."%");
            $query->orWhere('ean','like',"%".$request->filter."%");
            $query->orWhere('model_no','like',"%".$request->filter."%");

        }




        return $query;
    }

    public function getSoldItems(){
        return $this->hasMany(EbayOrderSoldDetails::class,'average_price_id','id');
    }
}
