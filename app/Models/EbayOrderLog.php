<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EbayOrderLog extends Model
{
    use HasFactory;
    protected $table = 'ebay_orders_log';

    protected $fillable = ['orders_id', 'content'];

    /*
     * instead of orders_id, it should be ebay_order_id
     * foreign key missing
     * */

}
