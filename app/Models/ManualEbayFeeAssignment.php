<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use App\Models\EbayOrders;

class ManualEbayFeeAssignment extends Model
{
    use HasFactory;
    protected $table = 'manual_ebay_fee_assignment';

    public function scopeFromRequest(Builder $query, Request $request, $type = 'main') {
        if ($request->sort) {
            $query->orderBy($request->sort, $request->sortO);
        }
        if ($request->fee_type) {
            $query->where('fee_type', $request->fee_type);
        }

        if ($request->invoice) {
            if ($request->invoice == "Yes") {
                $query->whereNotNull('invoice_number');
            } elseif ($request->invoice == "No") {
                $query->whereNull('invoice_number');
            }
        }

        if ($request->date) {
            $query->whereRaw("DATE_FORMAT(date,'%Y-%m-%d') ='" . $request->date . "'");
        }

        if ($request->field && $request->filter_value) {
            $query->where($request->field, 'like', "%$request->filter_value%");
        }
        return $query;
    }

    public function fees() {
        return $this->hasMany(EbayFees::class, 'id', 'fee_record_no');
    }
}
