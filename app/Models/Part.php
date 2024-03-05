<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Part extends Model
{
    use HasFactory;
    protected $fillable = [
        'name', 'name', 'colour', 'type', 'quantity', 'quantity_inbound', 'quantity_mprc', 'cost'
    ];

    public function part_logs()
    {
        return $this->hasMany(PartLog::class);
    }

    public function getCostFormattedAttribute()
    {
      //  return money_format(config('app.money_format'), $this->cost);
        return $this->cost;
    }

    public function getSalePriceFormattedAttribute()
    {
       // return money_format(config('app.money_format'), $this->sale_price);
        return $this->sale_price;
    }

    public function getLongNameAttribute()
    {
        $name = $this->name." - ".$this->colour." - ".$this->type;
        return $name;
    }

    public function suppliers(){

        return $this->hasOne(Supplier::class, 'id', 'supplier_id');
    }

    public function getImageUrlAttribute()
    {
        return asset('/img/parts/' . $this->image);
    }
}
