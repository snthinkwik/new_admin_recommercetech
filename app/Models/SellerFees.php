<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SellerFees extends Model
{
    use HasFactory;
    protected $table="seller_fees";
    protected $fillable=['platform','platform_fees','uk_shipping_cost_under_20','uk_shipping_cost_above_20','uk_non_shipping_cost_under_20','uk_non_shipping_above_under_20','accessories_cost_ex_vat','warranty_accrual'];

}
