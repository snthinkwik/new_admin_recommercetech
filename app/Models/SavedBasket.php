<?php

namespace App\Models;

use App\Models\Stock;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavedBasket extends Model
{
    use HasFactory;
    public function stock()
    {
        return $this->belongsToMany(Stock::class, 'saved_baskets_stock');
    }

    public function getTotalSalePriceAttribute()
    {
        return $this->stock()->sum('sale_price');
    }

    public function getTotalPurchasePriceAttribute()
    {
        return $this->stock()->sum('purchase_price');
    }

    public function getTotalSalePriceFormattedAttribute()
    {
        return money_format($this->total_sale_price);

    }

    public function getTotalPurchasePriceFormattedAttribute()
    {
        return money_format($this->total_purchase_price);

    }

    public function scopeSellable($query)
    {
        $query->whereDoesntHave('stock', function($q) {
            //$q->whereIn('status', [Stock::STATUS_READY_FOR_SALE, Stock::STATUS_IN_STOCK]);
            $q->whereIn('status', [Stock::STATUS_INBOUND,Stock::STATUS_PAID, Stock::STATUS_SOLD, Stock::STATUS_DELETED, Stock::STATUS_LOST, Stock::STATUS_REPAIR, Stock::STATUS_RETURNED_TO_SUPPLIER]);
        });
    }

}
