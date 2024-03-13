<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierReturnItem extends Model
{
    use HasFactory;

    public function supplier_return()
    {
        return $this->belongsTo(SupplierReturn::class);
    }

    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }
}
