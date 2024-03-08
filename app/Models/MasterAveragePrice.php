<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class MasterAveragePrice extends Model
{
    use HasFactory;
    protected $table="master_average_price";
    protected $fillable=['category','product_name','ean','model','mpn','condition','master_average_price','ebay_average_price','bm_average_price','price_diff',
        'diff_percentage','make','ma_product_id','product_id','ma_update_time','type','manual_price'];


    public function scopeFromRequest(Builder $query, Request $request) {

        if ($request->sort) {
            $query->orderBy($request->sort, $request->sortO);
        }
        if($request->product_id){
            $query->where('product_id',$request->product_id);
        }
        if($request->brand){
            $query->where('make',$request->brand);
        }
        if($request->validate){

            $query->where('validate',$request->validate);
        }
        if($request->platform){
            $query->where('platform',$request->platform);
        }
        if($request->condition){
            $query->where('condition',$request->condition);
        }
        if($request->time){
            if($request->time==="24 hours"){
                $query->where('updated_at','>',Carbon::now()->subDay()->toDateTimeString());
            }elseif($request->time==="within 1 week"){
                $query->where('updated_at','>',Carbon::now()->subDay(7)->toDateTimeString());
            }elseif($request->time==="more then 1 week"){
                $query->where('updated_at','<',Carbon::now()->subDay(7)->toDateTimeString());
            }

        }
        if($request->filter){

            $query->where('product_name' , 'LIKE' , '%'.$request->filter.'%');
            $query->orWhere('mpn','like',"%".$request->filter."%");
            $query->orWhere('ean','like',"%".$request->filter."%");
            $query->orWhere('model','like',"%".$request->filter."%");

        }




        return $query;
    }
}
