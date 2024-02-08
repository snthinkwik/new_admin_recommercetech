<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryNotes extends Model
{
    use HasFactory;
    protected $table='delivery_note';
    protected $fillable=['sales_id','notes'];
}
