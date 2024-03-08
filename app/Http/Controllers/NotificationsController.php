<?php

namespace App\Http\Controllers;

use App\Models\BatchOffer;
use App\Models\Invoice;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Http\Request;
use Cache;

class NotificationsController extends Controller
{
    public function getIndex()
    {
        $ordersAwaitingDispatch = Sale::whereIn('invoice_status', [Invoice::STATUS_READY_FOR_DISPATCH, Invoice::STATUS_PAID])->whereNull('other_recycler')->count();
        $whatsAppUsers = User::where('whatsapp', true)->where('whatsapp_added', false)->count();
        $batchOffersNotSeen = BatchOffer::where('seen', false)->count();
        $batchOffersNotSeenList = BatchOffer::where('seen', false)->groupBy('batch_id')->select('batch_id')->get();

        $data = new \stdClass();
        $data->orders_paid_awaiting_dispatch = $ordersAwaitingDispatch;
        $data->whats_app_users = $whatsAppUsers;
        $data->batch_offers_not_seen = $batchOffersNotSeen;
        $data->batch_offers_not_seen_list = $batchOffersNotSeenList;

        $data->notifications_unlocks = Cache::get('notifications.unlocks');

        $data->balance = Cache::get('click2unlock_balance');

        return view('notifications.index', compact('data'));
    }
}
