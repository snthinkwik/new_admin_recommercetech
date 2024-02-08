<?php

namespace App\Models;

use App\Models\EbayOrders;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class EbayOrderItems extends Model
{
    use HasFactory;

    protected $table = 'ebay_order_items';
    protected $fillable = [
        "order_id",
        "sales_record_number",
        "item_id",
        "external_id",
        "item_name",
        "item_sku",
        "quantity",
        "individual_item_price",
        "individual_item_discount_price",
        "tax_percentage",
        "giftwrap",
        "weight",
        "item_image",
        "owner",
        "item_number",
        "stock_id",
        "sale_type",
        'condition',
    ];

    const TRG = "TRG";
    const RECOMM = "Recomm";
    const CMT = "CMT";
    const LCDBUYBACK = "LCD Buyback";
    const CMN = "CMN";
    const REFURBSTORE = "Refurbstore";
    const UNKNOWN = "Unknown";
    const SALE_TYPE_BUY_IT_NOW = "Buy it Now";
    const SALE_TYPE_AUCTION = "Auction";

    public static function getAvailableOwner()
    {
        return [self::TRG, self::RECOMM, self::CMT, self::CMN, self::REFURBSTORE, self::UNKNOWN, self::LCDBUYBACK];
    }

    public static function getAvailableOwnerWithKeys()
    {
        return array_combine(self::getAvailableOwner(), self::getAvailableOwner());
    }

    public static function getAvailableSaleType()
    {
        return [self::SALE_TYPE_BUY_IT_NOW, self::SALE_TYPE_AUCTION];
    }

    public static function getAvailableSaleTypeWithKeys()
    {
        return array_combine(self::getAvailableSaleType(), self::getAvailableSaleType());
    }

    public function stock()
    {
        return $this->hasOne(Stock::class, 'id', 'stock_id');
    }

    public function stock_serial()
    {
        return $this->hasOne(Stock::class, 'serial', 'item_sku')->where("serial", "!=", "");
    }


    public function ebayOrder()
    {
        return $this->belongsTo(EbayOrders::class, 'id', 'order_id');
    }

    public function order()
    {
        return $this->hasOne(EbayOrders::class, 'id', 'order_id');
    }

    public function fees()
    {
        return $this->hasMany(EbayFees::class, 'sales_record_number', 'sales_record_number');
    }

    public function DpdInvoice()
    {
        return $this->hasMany(DpdInvoice::class, 'matched', 'sales_record_number');
    }

    public function matched_to_item()
    {
        return $this->hasMany(EbayFees::class, 'matched_to_order_item', 'id');
    }

    public function scopeFromRequest(Builder $query, Request $request, $type = 'main')
    {
        if ($request->sort) {
            $query->orderBy($request->sort, $request->sortO);
        }
        if ($request->invoice) {
            if ($request->invoice == "Yes") {
                $query->whereNotNull('invoice_number');
            } elseif ($request->invoice == "No") {
                $query->whereNull('invoice_number');
            }
        }
        if ($request->sale_type) {
            $query->where("sale_type", $request->sale_type);
        }

        if ($request->order_status) {
            $query->whereHas('order', function ($q) use ($request) {
                $q->where("status", $request->order_status);
            });
        }

        if ($request->field && $request->filter_value) {
            $query->where($request->field, 'like', "%$request->filter_value%");
        }
        if ($request->ready) {
            if ($request->ready == "Yes") {
                $query->whereIn('sale_type', [self::SALE_TYPE_BUY_IT_NOW, self::SALE_TYPE_AUCTION])
                    ->with('matched_to_item')
                    ->whereHas('matched_to_item', function ($q) {
                        $q->whereIn('fee_type', ['Final Value Fee', 'Insertion Fee']);
                    })
                    ->whereHas('order', function ($q) {
                        $q->whereNotNull('paypal_fees');
                    })
                    ->has('DpdInvoice', '>', 0);
            } else {
                $query->whereIn('sale_type', [self::SALE_TYPE_BUY_IT_NOW, self::SALE_TYPE_AUCTION])
                    ->has('matched_to_item', "=", 0)
                    ->orWhereHas('order', function ($q) {
                        $q->whereNull("paypal_fees");
                    })
                    ->has("DpdInvoice", "=", 0);
            }
        }
        return $query;
    }

    public function scopeReadyForInvoice($query)
    {
        $query->where('owner', self::RECOMM)
            ->where(function ($q) {
                $q->where(function ($w) {
                    $w->whereHas('order', function ($o) {
                        $o->whereIn('status', [EbayOrders::STATUS_REFUNDED, EbayOrders::STATUS_CANCELLED]);
                    });
                });
                $q->orWhere(function ($w) {
                    $w->whereIn('sale_type', [self::SALE_TYPE_BUY_IT_NOW]);
                    $w->whereHas('matched_to_item', function ($m) {
                        $m->whereIn('fee_type', ['Final Value Fee']);
                    });
                    $w->whereHas('order', function ($o) {
                        $o->whereNotNull('paypal_fees');
                        $o->whereIn('status', [EbayOrders::STATUS_DISPATCHED]);
                        $o->has('DpdImport');
                    });
                });
                $q->orWhere(function ($w) {
                    $w->whereIn('sale_type', [self::SALE_TYPE_AUCTION]);
                    $w->whereHas('matched_to_item', function ($m) {
                        $m->whereIn('fee_type', ['Insertion Fee']);
                    });
                    $w->whereHas('matched_to_item', function ($m) {
                        $m->whereIn('fee_type', ['Final Value Fee']);
                    });
                    $w->whereHas('order', function ($o) {
                        $o->whereNotNull('paypal_fees');
                        $o->whereIn('status', [EbayOrders::STATUS_DISPATCHED]);
                        $o->has('DpdImport');
                    });
                });
            });

        return $query;
    }

}
