<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

class BasketController extends Controller
{
    public function getIndex()
    {
        $basket = Auth::user()->basket;
        $part_basket = Auth::user()->part_basket;
        return view('basket.index', compact('basket', 'part_basket'));
    }

    public function getHtml()
    {
        $basket = Auth::user()->basket;
        $part_basket = Auth::user()->part_basket;

        return response()->json([
            'basketHtml' => View::make('basket.navbar', ['basket' => $basket, 'part_basket' => $part_basket])->render(),
        ]);
    }

    public function postToggle(Request $request)
    {
        $ids = $request->ids ?: [$request->id];

        if ($request->in_basket) {
            Auth::user()->basket()->sync($ids, false);
        }
        else {
            Auth::user()->basket()->detach($ids);
        }

        $basket = Auth::user()->fresh(['basket'])->basket;
        $part_basket = Auth::user()->part_basket;

        return response()->json([
            'status' => 'success',
            'basketHtml' => View::make('basket.navbar', ['basket' => $basket, 'part_basket' => $part_basket])->render(),
        ]);
    }

    public function postEmpty()
    {
        Auth::user()->basket()->sync([]);
        return redirect('stock');
    }

    public function postDelete(Request $request)
    {
        $item = Stock::findOrFail($request->id);
        Auth::user()->basket()->detach($item->id);
        return back()->with('messages.success', "Item removed from basket: $item->long_name.");
    }

    public function getDeleteItem(Request $request)
    {
        $item = Stock::findOrFail($request->id);
        Auth::user()->basket()->detach($item->id);
        $basketItems = Auth::user()->basket->lists('id');
        if(!count($basketItems)) {
            return redirect()->route('basket');
        }

        $items = array_combine($basketItems, array_fill(0, count($basketItems), ''));
        return redirect()->route('sales.summary', ['items' => $items]);
    }
}
