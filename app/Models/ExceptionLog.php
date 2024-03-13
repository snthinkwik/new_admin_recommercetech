<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExceptionLog extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'method', 'route', 'url', 'command', 'exception_short', 'exception_long'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
