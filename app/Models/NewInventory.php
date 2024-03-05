<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class NewInventory extends Model
{
    use HasFactory;
    protected $table='new_inventory';
    protected $fillable=['product_category','product_id','make','product_name','non_serialised','model','mpn','ean','status','vat_type'
        ,'total_purchase_price','qty_in_stock','qty_in_tested','qty_in_bound','grade_a','grade_b','grade_c','grade_d','grade_e','cracked_back',
        'no_touch_face_id','network_locked','retail_comparison','grade'];

    public function scopeFromRequest(Builder $query, Request $request, $type = 'main')
    {


        $query = self::query();




        //$term=  str_replace(array('(', ')','-'), array('', '',''), $request->term);

        if($request->status){
            $query->where('status', $request->status);
        }
        if($request->vat_type){

            $query->where('vat_type', $request->vat_type);
        }
        if($request->grade){
            $query->where('grade', $request->grade);
        }

        if($request->term){
            $query->where('product_name', 'like', "%" . $request->term . "%");
        }
        if($request->product_type){
            $query->where('product_category', $request->product_type);
        }


        return $query;
    }
}
