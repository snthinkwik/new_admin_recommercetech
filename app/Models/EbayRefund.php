<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class EbayRefund extends Model
{
    use HasFactory;
    protected $table = 'ebay_refunds';
    protected $fillable = [
        'order_id',
        'sales_record_number',
        'refund_amount',
        'processed',
        'owner'
    ];

    public function order() {
        return $this->hasOne(EbayOrders::class, 'id', 'order_id');
    }

    public function scopeFromRequest(Builder $query, Request $request, $type = 'main') {

        if ($request->sort) {
            $query->orderBy($request->sort, $request->sortO);
        }

        if ($request->processed) {
            $query->where('processed', $request->processed);
        }

        if ($request->sales_record_number) {
            $query->where('sales_record_number', 'like', "%$request->sales_record_number%");
        }

        if ($request->owner) {
            $query->where('owner', $request->owner);
        }

        return $query;
    }

    public static function getRefund($owner = '') {
        return \App\Models\EbayRefund::where('owner', $owner)->sum("refund_amount");
    }

}
