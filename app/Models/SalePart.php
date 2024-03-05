<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalePart extends Model
{
    use HasFactory;
    protected $table = 'new_sales_parts';

    protected $fillable = [
        'sale_id', 'part_id', 'quantity', 'snapshot_name', 'snapshot_colour', 'snapshot_type', 'snapshot_sale_price'
    ];

    public function part()
    {
        return $this->belongsTo(Part::class);
    }

    public function getLongNameAttribute()
    {
        $name = $this->snapshot_name." - ".$this->snapshot_colour." - ".$this->snapshot_type;
        return $name;
    }

    public function getLongNameQtyAttribute()
    {
        $name = $this->quantity."x ".$this->snapshot_name." - ".$this->snapshot_colour." - ".$this->snapshot_type;
        return $name;
    }

}
