<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RepairEngineer extends Model
{
    use HasFactory;
    protected $table = 'repairs_engineer';
    protected $fillable = ['name','company'];
}
