<?php

namespace App\Http\Controllers;

use App\Models\SavedBasket;
use Illuminate\Http\Request;

class SavedBasketController extends Controller
{
    public function getIndex(Request $request)
    {
        $savedBaskets = SavedBasket::sellable()->orderBy('id', 'desc')->paginate(config('app.pagination'));

        return view('saved-baskets.index', compact('savedBaskets'));
    }

    public function getSingle($id)
    {
        $savedBasket = SavedBasket::findOrFail($id);


        $totalProfit=[];
        $totalTrueProfit=[];
        $profitPercentage='';
        $trueProfitPercentage=[];
        $totalExVatPrice=[];
        $totalSalePrice=[];
        $totalTrueExVatPrice=[];
        $totalTrueSalePrice=[];
        foreach ($savedBasket->stock as $stock)
        {
            if(!is_null($stock->profit)){
                array_push($totalProfit,$stock->profit);
                if($stock->vat_type ==="Standard"){
                    array_push($totalExVatPrice,$stock->total_price_ex_vat);
                }else{
                    array_push($totalSalePrice,$stock->sale_price);
                }
            }
            if(!is_null($stock->true_profit)){
                array_push($totalTrueProfit,$stock->true_profit);
                if($stock->vat_type ==="Standard"){
                    array_push($totalTrueExVatPrice,$stock->total_price_ex_vat);
                }else{
                    array_push($totalTrueSalePrice,$stock->sale_price);
                }


            }
        }

        if(count($totalProfit)){
            if( count($totalExVatPrice)>0){
                $profitPercentage=number_format(array_sum($totalProfit)/array_sum($totalExVatPrice) * 100,2);
            }else{
                $profitPercentage=number_format(array_sum($totalProfit)/array_sum($totalSalePrice) * 100,2);
            }
        }
        if(count($totalTrueProfit)){
            if( count($totalTrueExVatPrice)>0){
                $trueProfitPercentage=number_format(array_sum($totalTrueProfit)/array_sum($totalTrueExVatPrice) * 100,2);
            }else{
                $trueProfitPercentage=number_format(array_sum($totalTrueProfit)/array_sum($totalTrueSalePrice) * 100,2);
            }

        }

        return view('saved-baskets.single', compact('savedBasket','totalProfit','totalTrueProfit','profitPercentage','trueProfitPercentage'));
    }

    public function postCreateSale(Request $request)
    {
        $savedBasket = SavedBasket::findOrFail($request->id);

        $ids = $savedBasket->stock->lists('id');

        return redirect()->route('sales.new', compact('ids'));
    }

    public function postDelete(Request $request)
    {
        $savedBasket = SavedBasket::findOrFail($request->id);
        $savedBasket->delete();

        return redirect()->route('saved-baskets')->with('messages.success', 'Basket has been removed');
    }

    public function postDeleteFromBasket(Request $request)
    {
        $savedBasket = SavedBasket::findOrFail($request->id);
        $savedBasket->stock()->detach($request->stock_id);

        return back()->with('messages.success', 'Removed from Basket');
    }
}
