<?php

namespace App\Models;

use App\Models\BatchOffer;
use App\Models\Invoice;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Notification extends Model
{
    use HasFactory;
    public static function getNotificationsCount()
    {
        $count = 0;
        $ordersAwaitingDispatch = Sale::whereIn('invoice_status', [Invoice::STATUS_READY_FOR_DISPATCH, Invoice::STATUS_PAID])->whereNull('other_recycler')->count();
        $batchOffersNotSeen = BatchOffer::where('seen', false)->count();

        $notificationsUnlocks =!is_null(Cache::get('notifications.unlocks')) ? count(Cache::get('notifications.unlocks')):0;
        $whatsAppUsers = User::where('whatsapp', true)->where('whatsapp_added', false)->count();

        $count = $ordersAwaitingDispatch + $batchOffersNotSeen + $notificationsUnlocks + $whatsAppUsers;

        return $count;
    }
}
