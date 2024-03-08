<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChannelGrabberUpdateLog extends Model
{
    use HasFactory;
    protected $fillable = ['cron', 'sku_qty', 'found_qty', 'updated_qty', 'not_found_qty', 'update_error_qty', 'details'];

    protected $casts = ['details' => 'json'];

}
