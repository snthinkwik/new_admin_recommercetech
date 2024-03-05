<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartLog extends Model
{
    use HasFactory;
    protected $fillable = ['part_id', 'user_id', 'content'];

    public function part()
    {
        return $this->belongsTo(Part::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
