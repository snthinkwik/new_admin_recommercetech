<?php

namespace App\Http\Controllers;

use App\Contracts\Invoicing;
use App\Models\Invoice;
use App\PhoneCheck;
use App\Sale;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        dd("ddkd");
        return redirect()->route('stock');
        return view('home');
    }

    public function getRedirect()
    {
        if(Auth::user()) {
            return redirect()->route('home');
        }

        return redirect()->route('stock');
    }

    public function getIndex()
    {

        return redirect()->route('stock');
    }

    public function getTvStats(Invoicing $invoicing, Request $request)
    {
        $sales = Sale::whereIn('invoice_status', [Invoice::STATUS_PAID, Invoice::STATUS_READY_FOR_DISPATCH])->whereNull('other_recycler')->get();
        $newOrders = Sale::whereIn('invoice_status', [Invoice::STATUS_OPEN])->whereNull('other_recycler')->get();
        $newOrdersIds = collect($newOrders)->keyBy('customer_api_id')->lists('customer_api_id');
        $ids = collect($sales)->keyBy('customer_api_id')->lists('customer_api_id');

        $ids = array_merge($newOrdersIds, $ids);
        $ids = array_unique($ids);

        $customers = $invoicing->getRegisteredSelectedCustomers($ids)->keyBy('external_id');

        if ($request->ajax()) {
            return response()->json([
                'tvStatsItemsHtml' => View::make('home.tv-stats-list', compact('sales', 'customers', 'newOrders'))->render(),
            ]);
        }

        return view('home.tv-stats', compact('sales', 'customers', 'newOrders'));
    }

    public function getTv2Stats(Request $request)
    {
        die('unavailable');

        if ($request->ajax()) {
            return response()->json([
                'tv2StatsItemsHtml' => View::make('home.tv2-stats-list', compact('engineersOut', 'engineersCompleted', 'devicesAwaiting'))->render(),
            ]);
        }

        return view('home.tv2-stats', compact('engineersOut', 'engineersCompleted', 'devicesAwaiting'));
    }

    public function getTv3Stats(Request $request)
    {
        $last24hours = Carbon::now()->subHours(24);
        $sales = Sale::whereNotIn('invoice_status', ['voided'])->where('created_at', '>=', $last24hours)->get();

        $data = new \stdClass();
        $data->total_sales = $sales->sum('amount');
        $data->total_profit = $sales->sum('profit_amount');

        if($request->ajax()) {
            return response()->json([
                'tv3StatsItemsHtml' => View::make('home.tv3-stats-list', compact('data'))->render(),
            ]);
        }

        return view('home.tv3-stats', compact('data'));


    }

    public function getTv4Stats(Request $request)
    {
        $ordersTrg = Sale::whereIn('invoice_status', [Invoice::STATUS_READY_FOR_DISPATCH, Invoice::STATUS_PAID])->whereNull('other_recycler')->count();

        if($request->ajax()) {
            return response()->json([
                'tv4StatsItemsHtml' => View::make('home.tv4-stats-list', compact('ordersTrg'))->render(),
            ]);
        }

        return view('home.tv4-stats', compact('ordersTrg', 'tradeInPacks'));
    }

    public function getTv5Stats(Request $request)
    {
        $recentItems = PhoneCheck::where('status', PhoneCheck::STATUS_DONE)->has('stock')->orderBy('updated_at', 'desc')->limit(5)->get();

        $engineers = [];

        $engineersList = User::where('station_id', '!=', '')->select('station_id', 'first_name')->get();
        foreach($engineersList as $engineer) {
            $all = DB::table('phone_checks')->where('station_id', $engineer->station_id)->count();
            if($all) {
                $inactive = [];
                $lastDate = Carbon::createFromFormat("Y-m-d H:i:s",DB::table('phone_checks')->where('station_id', $engineer->station_id)->orderBy('id', 'desc')->limit(1)->first()->created_at);
                if(Carbon::now()->diffInHours($lastDate) > 24) {
                    continue;
                }
                $inactiveRaw = Carbon::now()->diff($lastDate);
                if($inactiveRaw->m) $inactive[] = $inactiveRaw->m." months";
                if($inactiveRaw->d) $inactive[] = $inactiveRaw->d." days";
                if($inactiveRaw->h) $inactive[] = $inactiveRaw->h." hours";
                $inactive[] = $inactiveRaw->i." mins";
                $inactive[] = $inactiveRaw->s." seconds";
                $engineers[] = [
                    'first_name' => $engineer->first_name,
                    'today' => DB::table('phone_checks')->where('station_id', $engineer->station_id)->where('created_at', '>=', Carbon::now()->startOfDay())->where('created_at', '<=', Carbon::now()->endOfDay())->count(),
                    'month' => DB::table('phone_checks')->where('station_id', $engineer->station_id)->where('created_at', '>=', Carbon::now()->startOfMonth())->where('created_at', '<=', Carbon::now()->endOfMonth())->count(),
                    'all' => $all,
                    'inactive' => implode(", ", $inactive),
                ];
            }
        }
        $engineers = json_decode(json_encode($engineers));


        if($request->ajax()) {
            return response()->json([
                'tv5StatsItemsHtml' => View::make('home.tv5-stats-list', compact('recentItems', 'engineers'))->render(),
            ]);
        }

        return view('home.tv5-stats', compact('recentItems', 'engineers'));
    }
}
