<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EbayNetwork extends Model
{
    use HasFactory;
    protected $table='ebay_network';
    protected $fillable=['item_id','network'];
}
