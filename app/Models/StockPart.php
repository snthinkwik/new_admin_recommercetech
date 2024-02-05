<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockPart extends Model
{
    use HasFactory;
    protected $fillable = ['stock_id', 'part_id', 'cost'];

    protected $table = 'new_stock_parts';

    public function getPartCostAttribute()
    {
        return $this->cost > 0 ? $this->cost : $this->part->cost;
    }

    public function getCostFormattedAttribute()
    {
       // return money_format(config('app.money_format'), $this->part_cost);
        return  $this->part_cost;
    }

    public function getCreatedAtFormattedAttribute()
    {
        return $this->created_at->timestamp > 0 ? $this->created_at : null;
    }

    public function part()
    {
        return $this->belongsTo(Part::class);
    }
}
