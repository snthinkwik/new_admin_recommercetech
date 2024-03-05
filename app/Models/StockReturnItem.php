<?php

namespace App\Models;

use App\StockReturnLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class StockReturnItem extends Model
{
    use HasFactory;
    const RETURN_REASON_DEVICE_IS_NOT_AS_DESCRIBED = "The device is not as described";
    const RETURN_SUBREASON_WORSE_CONDITION = "The device is in worse condition than I was expecting";
    const RETURN_SUBREASON_MAJOR_FAULT = "The device has a major fault";
    const RETURN_SUBREASON_NOT_POWER_ON = "The device will not power on";
    const RETURN_SUBREASON_ANOTHER_REASON = "The device has another reason for return";
    const RETURN_REASON_DEVICE_IS_LOCKED_TO_A_NETWORK = "The device is locked to a network";
    const RETURN_REASON_DEVICE_IS_ICLOUD_LOCKED = "The device is iCloud locked";

    const STATUS_NEW = "New";
    const STATUS_APPROVED = "Approved";
    const STATUS_PENDING_ASSESSMENT = "Pending Assessment";
    const STATUS_REJECTED = "Rejected";
    const STATUS_CREDITED = "Credited";

    protected $dates = ['date_of_purchase'];

    public function stock_return()
    {
        return $this->belongsTo(StockReturn::class, 'stock_return_id', 'id');
    }

    public function stock()
    {
        return $this->belongsTo(Stock::class, 'stock_id', 'id');
    }

    public static function getAvailableStatuses($keys = false)
    {
        $statuses = [
            self::STATUS_NEW,
            self::STATUS_APPROVED,
            self::STATUS_PENDING_ASSESSMENT,
            self::STATUS_REJECTED,
            self::STATUS_CREDITED,
        ];

        return $keys ? array_combine($statuses, $statuses) : $statuses;
    }

    public function save(array $options = array())
    {
        $changes = '';
        $exists = $this->exists;
        foreach ($this->attributes as $key => $value)
        {
            if (!array_key_exists($key, $this->original))
            {
                $changes .= "Added value \"$value\" for field \"$key\".\n";
            }
            elseif ($value !== $this->original[$key] && !checkUpdatedFields($value, $this->original[$key]))
            {
                $changes .= "Changed value of field \"$key\" from \"{$this->original[$key]}\" to \"$value\".\n";
            }
        }

        if ($changes) {
            $user = Auth::user();
            if ($user) {
                $changes .= "User ID: \"$user->id\" (name \"$user->full_name\").\n";
            }

            if (!empty($GLOBALS['argv'])) {
                $changes .= "Cron: \"" . implode(' ', $GLOBALS['argv']) . "\"";
            }
        }

        $res =  parent::save($options);

        if($changes) {
            \App\Models\StockReturnLog::create([
                'user_id' => $user ? $user->id: null,
                'stock_return_id' => $this->stock_return_id,
                'stock_return_item_id' => $this->id,
                'content' => ($exists ? "Updated: \n" : "Created: \n").$changes
            ]);
        }
    }
}
