<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrgStock extends Model
{
    use HasFactory;
    protected $connection = 'stock_ebdb';
    protected $table = 'new_stock';
}
