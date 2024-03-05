<?php

namespace App\Models;

use App\Models\Sale;
use App\Models\Stock;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewSalesStock extends Model
{
    use HasFactory;
    protected $table="new_sales_stock";


    public function stock(){

        return $this->hasOne(Stock::class,'id','stock_id');

    }
    public function sales(){
        return $this->hasMany(Sale::class,'id','sale_id');
    }

}
