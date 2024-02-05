<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\EbayOrders;

class EbaySaleHistory extends Model
{
    use HasFactory;
    protected $table = 'ebay_sale_history';

    public function order() {
        return $this->hasOne(EbayOrders::class, 'id', 'master_ebay_order_id');
    }
}
