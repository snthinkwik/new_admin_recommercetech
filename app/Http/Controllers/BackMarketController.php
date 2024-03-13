<?php

namespace App\Http\Controllers;

use App\Models\BackMarketUpdateLog;
use App\Models\Stock;
use Illuminate\Http\Request;

class BackMarketController extends Controller
{
    public function getIndex()
    {
        return redirect()->route('back-market.update-logs');
        $backMarket = app('App\Contracts\BackMarket');

        $res = $backMarket->makeGetRequest('listings', ['page' => 4]);
        dd($res);
    }

    public function getUpdateLogs(Request $request)
    {
        $logs = BackMarketUpdateLog::orderBy('id', 'desc')->paginate(config('app.pagination'));

        return view('back-market.index', compact('logs'));
    }

    public function getUpdateLogsSingle($id)
    {
        $log = BackMarketUpdateLog::findOrFail($id);
        $logDetails = json_decode(json_encode($log->details));
        $notFoundSkus = $logDetails->not_found;
        foreach($notFoundSkus as $key => $notFoundSku) {
            $notFoundSkus[$key] = [
                'sku' => $notFoundSku,
                'items' => Stock::where('status', Stock::STATUS_RETAIL_STOCK)->where('new_sku', '!=', '')->where('new_sku', $notFoundSku)->lists('id')
            ];
        }
        $logDetails->not_found = json_decode(json_encode($notFoundSkus));

        return view('back-market.single', compact('log', 'logDetails'));
    }

    public function postCronSettings(Request $request)
    {
        Setting::set('crons.back-market-update-retail-stock-quantities.enabled', $request->enabled);
        $message = "saved";
        if(!$request->enabled) {
            artisan_call_background('back-market:update-retail-stock-quantities', ['qty-zero' => 'true']);
            $message.=", cron pushing 0 qty has been started.";
        }
        return back()->with('messages.success', $message);
    }
}
