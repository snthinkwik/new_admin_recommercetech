<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RepairType extends Model
{
    use HasFactory;
    protected $table = 'repairs_type';
    protected $fillable = ['name'];

    const TYPE_LEVEL_1 = 1;
    const TYPE_LEVEL_2 = 2;
    const TYPE_LEVEL_3 = 3;
}
