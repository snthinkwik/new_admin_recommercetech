<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhoneCheckReports extends Model
{
    use HasFactory;
    protected $table='phone_check_report';
    protected $fillable=['stock_id','ean','report_id','report'];
}
