<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;
    const CREATION_STATUS_NOT_INITIALISED = 'not initialised';
    const CREATION_STATUS_IN_PROGRESS = 'in progress';
    const CREATION_STATUS_SUCCESS = 'success';
    const CREATION_STATUS_ERROR = 'error';

    const STATUS_OPEN = 'open';
    const STATUS_PAID = 'paid';
    const STATUS_READY_FOR_DISPATCH = 'ready for dispatch';
    const STATUS_DISPATCHED = 'dispatched';
    const STATUS_VOIDED = 'voided';
    const STATUS_PAID_ON_INVOICE = 'paid on invoice';

    public static function getAvailableStatuses()
    {
        return array_values(self::getAvailableStatusesWithKeys());
    }

    public static function getAvailableStatusesWithKeys()
    {
        $statuses = [
            self::STATUS_OPEN, self::STATUS_PAID_ON_INVOICE, self::STATUS_PAID, self::STATUS_READY_FOR_DISPATCH, self::STATUS_DISPATCHED, self::STATUS_VOIDED
        ];

        return array_combine(
            $statuses,
            array_map('ucfirst', $statuses)
        );
    }
}
