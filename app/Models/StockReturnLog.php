<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockReturnLog extends Model
{
    use HasFactory;
    protected $fillable = ['stock_return_id', 'stock_return_item_id', 'user_id', 'content'];

    public function stock_return()
    {
        return $this->belongsTo(StockReturn::class);
    }

    public function stock_return_item()
    {
        return $this->belongsTo(StockReturnItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
