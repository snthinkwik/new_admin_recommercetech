<?php

namespace App\Models;

use App\Database\Scopes\ValueNotEmpty;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    use HasFactory;
    protected $table = 'grade';

    protected static function boot()
    {
        self::addGlobalScope(new ValueNotEmpty('condition_grade'));
        parent::boot();
    }

}
