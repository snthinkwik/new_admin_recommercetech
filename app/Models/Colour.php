<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Colour extends Model
{
    use HasFactory;
    protected $table = 'colour';

    public $timestamps = false;

    protected $fillable = ['pr_colour', 'code'];
}
