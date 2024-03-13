<?php

namespace App\Http\Controllers;

use App\Models\BackMarketAveragePrice;
use App\Models\BackMarketEawData;
use App\Models\SellerFees;
use App\Models\Stock;
use Illuminate\Http\Request;

class AveragePriceBackMarketController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request)
    {


        $backMarket = BackMarketAveragePrice::with('maxPrice')->where('price_for_buybox', '>', 0)->fromRequest($request);
        $backMarket = $backMarket->paginate(config('app.pagination'))
            ->appends(array_filter(array_reverse($request->all())));

        $buyBoxYes = BackMarketAveragePrice::where('same_merchant_winner', 1)->count();
        $buyBoxNo = BackMarketAveragePrice::where('same_merchant_winner', 0)->count();
        $sellerFees = SellerFees::where('platform', Stock::PLATFROM_BACKMARCKET)->first();


        if ($request->ajax()) {
            return response()->json([
                'itemsHtml' => View::make('average-back-market-price.list', compact('backMarket', 'sellerFees', 'buyBoxYes', 'buyBoxNo'))->render(),
                'paginationHtml' => '' . $backMarket->render(),
                'sort' => $request->sort,
                'sortO' => $request->sortO,
            ]);
        }
        return view('average-back-market-price.index', compact('backMarket', 'sellerFees', 'buyBoxYes', 'buyBoxNo'));
    }


    public function removeAllDataFromTable()
    {
        BackMarketAveragePrice::truncate();
        BackMarketEawData::truncate();


        dd("done");
    }

    public function getRawData(){
        $rawData=BackMarketEawData::paginate(config('app.pagination'));
        $count=BackMarketEawData::count();


        return view('average-back-market-price.raw-data',compact('rawData','count'));

    }
}
