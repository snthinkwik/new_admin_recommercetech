<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Stock;

class StockLog extends Model
{
    use HasFactory;
    protected $table = 'stock_log';

    protected $fillable = ['user_id', 'stock_id', 'content'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }
}
