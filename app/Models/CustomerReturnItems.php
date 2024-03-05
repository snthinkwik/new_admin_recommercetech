<?php

namespace App\Models;

use App\Models\Stock;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerReturnItems extends Model
{
    use HasFactory;
    protected $table='customer_return_items';
    protected $fillable=['customer_return_id','name','purchase_cost','sale_price','item_sale_profit','return_reason','sale_id','qb_invoice_id','status','stock_id'];
    public function stock(){
        return $this->hasOne(Stock::class, 'id', 'stock_id');
    }

}
