<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTake extends Model
{
    use HasFactory;
    protected $fillable = ['stock_id', 'user_id'];

    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeFromRequest(Builder $query, Request $request, $type = 'main') {

        if ($request->sort) {
            $query->orderBy($request->sort, $request->sortO);
        }

        return $query;
    }
}
