<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RepairStatus extends Model
{
    use HasFactory;
    protected $table = 'repairs_status';
    protected $fillable = ['name'];

    const STATUS_OPEN = 1;
    const STATUS_IN_REPAIR = 2;
    const STATUS_CLOSED = 3;
    const STATUS_AWAITING_PART = 4;
}
