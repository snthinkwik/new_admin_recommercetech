<?php

namespace App\Http\Controllers;

use App\Models\Mobicode\GsxCheck;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MobicodeController extends Controller
{
    public function postGSXcheck(Request $request)
    {
        $stock = Stock::findOrFail($request->stock_id);
        $imei = $stock->imei;

        if(!$imei || !in_array(strlen($imei), [15,16])) {
            return back()->with('messages.danger', 'Invalid IMEI');
        }

        GsxCheck::create([
            'user_id' => Auth::user()->id,
            'stock_id' => $stock->id,
            'imei' => $stock->imei,
            'status' => GsxCheck::STATUS_NEW,
            'service_id' => 118
        ]);

        return back()->with('messages.success', 'Network check has been submitted.');
    }
}
