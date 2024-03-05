<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailWebhook extends Model
{
    use HasFactory;
    const STATUS_NEW = "New";
    const STATUS_PROCESSED = "Processed";

    const EVENT_OPENED = "opened";
    const EVENT_CLICKED = "clicked";
    const EVENT_FAILED = "failed";
    const EVENT_SPAM = "complained";
    const EVENT_DELIVERED = "delivered";
    const EVENT_TEMPORARY_FAILED = "temporary failed";

    protected $fillable = ['response', 'status'];

    protected $dates = ['event_time'];

    public function email_tracking()
    {
        return $this->belongsTo(EmailTracking::class);
    }

    public static function getAvailableTypes()
    {
        return [self::EVENT_DELIVERED, self::EVENT_OPENED, self::EVENT_CLICKED, self::EVENT_FAILED, self::EVENT_SPAM];
    }

    public static function getAvailableTypesWithKeys()
    {
        return array_combine(self::getAvailableTypes(), self::getAvailableTypes());
    }
}
