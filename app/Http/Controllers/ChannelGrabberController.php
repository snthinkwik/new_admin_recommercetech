<?php

namespace App\Http\Controllers;

use App\Models\ChannelGrabberUpdateLog;
use App\Models\Stock;
use Illuminate\Http\Request;

class ChannelGrabberController extends Controller
{
    public function getIndex()
    {
        return redirect()->route('channel-grabber.update-logs');
    }

    public function getUpdateLogs(Request $request)
    {
        $logs = ChannelGrabberUpdateLog::orderBy('id', 'desc')->paginate(config('app.pagination'));

        return view('channel-grabber.index', compact('logs'));
    }

    public function getUpdateLogsSingle($id)
    {
        $log = ChannelGrabberUpdateLog::findOrFail($id);
        $logDetails = json_decode(json_encode($log->details));
        $notFoundSkus = $logDetails->not_found;
        foreach($notFoundSkus as $key => $notFoundSku) {
            $notFoundSkus[$key] = [
                'sku' => $notFoundSku,
                'items' => Stock::where('status', Stock::STATUS_RETAIL_STOCK)->where('new_sku', '!=', '')->where('new_sku', $notFoundSku)->lists('id')
            ];
        }
        $logDetails->not_found = json_decode(json_encode($notFoundSkus));

        return view('channel-grabber.single', compact('log', 'logDetails'));
    }
}
