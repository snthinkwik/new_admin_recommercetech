<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EbayDeliveryCharges extends Model
{
    use HasFactory;
    protected $table = 'ebay_delivery_charges';

    public function order() {
        return $this->hasOne(EbayOrders::class, 'sales_record_number', 'sales_record_number');
    }

    public static function getDeliveryFees($owner = '') {
        return \App\Models\EbayDeliveryCharges::whereIn("sales_record_number", array_map('current', \App\EbayOrders::with("EbayOrderItems")
            ->whereHas('EbayOrderItems', function($q) use($owner) {
                $q->where("owner", $owner);
            })
            ->select("sales_record_number")
            ->get()
            ->toArray()))
            ->sum("cost");
    }

}
