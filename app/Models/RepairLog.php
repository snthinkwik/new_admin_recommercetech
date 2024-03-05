<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class RepairLog extends Model
{
    use HasFactory;
    protected $table = 'repairs_log';
    protected $fillable = ['item_id', 'user_id', 'content'];

    public function user() {
        return $this->belongsTo(User::class);
    }
}
