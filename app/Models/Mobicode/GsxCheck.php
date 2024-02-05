<?php

namespace App\Models\Mobicode;

use App\Models\Sale;
use App\Models\Stock;
use App\Models\Unlock;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GsxCheck extends Model
{
    use HasFactory;

    const STATUS_NEW = "new";
    const STATUS_PROCESSING = "processing";
    const STATUS_DONE = "done";
    const STATUS_FAILED = "failed";
    const STATUS_ERROR = "error";

    const SERVICE_CAPACITY_COLOUR_CHECK = 92;
    const SERVICE_ICLOUD_STATUS_CHECK = 82;
    const SERVICE_APPLE_BASIC_CHECK = 118; //107; //14;
    const SERVICE_ICLOUD_CHECK = 97;
    const SERVICE_LOCKED_UNLOCKED_CHECK = 118; //107; //17;

    protected $table = 'mobicode_gsx_checks';

    protected $fillable = ['user_id', 'stock_id', 'unlock_id', 'sale_id', 'external_id', 'service_id', 'imei', 'status', 'response', 'report'];

    /*public function __construct(array $attributes = array())
    {
        parent::__construct($attributes);
        // Database name prepended because of the bug in \Illuminate\Database\Eloquent::has()
        $this->table = DB::connection()->getDatabaseName() . '.' . 'mobicode_gsx_checks';
    }*/

    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }

    public function unlock()
    {
        return $this->belongsTo(Unlock::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

}
