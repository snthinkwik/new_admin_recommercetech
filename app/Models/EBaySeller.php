<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EBaySeller extends Model
{
    use HasFactory;
    protected $table='ebay_sellers';
    protected  $fillable=['name','user_name'];
}
