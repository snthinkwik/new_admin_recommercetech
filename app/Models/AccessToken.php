<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccessToken extends Model
{
    use HasFactory;
    protected $table = 'access_token';
    protected $fillable=['platform','access_token','expires_in','refresh_token','refresh_token_expires_in','token_type'];

}
