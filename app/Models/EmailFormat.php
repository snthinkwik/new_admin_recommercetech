<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailFormat extends Model
{
    use HasFactory;
    protected $table='email_format';
    protected $fillable=['email_format_name','subject','message','regard'];
}
