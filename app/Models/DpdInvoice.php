<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;


class DpdInvoice extends Model
{
    use HasFactory;

    protected $table = 'dpd_imports';
    protected $fillable = [
        'date',
        'consignment_number',
        'parcel_number',
        'product_description',
        'service_description',
        'delivery_post_code',
        'cost',
        'owner',
        'matched'
    ];

    public function scopeFromRequest(Builder $query, Request $request, $type = 'main') {
        if ($request->sort) {
            $query->orderBy($request->sort, $request->sortO);
        }
        if ($request->field && $request->filter_value) {
            $query->where($request->field, 'like', "%$request->filter_value%");
        }
        if ($request->owner) {
            $query->where('owner', $request->owner);
        }
        if ($request->matched) {
            if ($request->matched == "Yes") {
                $query->whereNotNull('matched');
                $query->where('matched', '!=', 'N/A');
            } elseif ($request->matched == "N/A") {
                $query->where('matched', $request->matched);
            } else {
                $query->whereNull('matched');
            }
        }

        return $query;
    }

    public function order() {
        return $this->hasOne(EbayOrders::class, 'sales_record_number', 'matched');
    }

    public static function getDeliveryFees($owner = '') {
        return \App\Models\DpdInvoice::whereIn("matched", array_map('current', \App\EbayOrders::with("EbayOrderItems")
            ->whereHas('EbayOrderItems', function($q) use($owner) {
                $q->where("owner", $owner);
            })
            ->select("sales_record_number")
            ->get()
            ->toArray()))
            ->sum("cost");
    }

}
