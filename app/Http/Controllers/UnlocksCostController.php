<?php

namespace App\Http\Controllers;

use App\Models\UnlockCost;
use Illuminate\Http\Request;

class UnlocksCostController extends Controller
{
    public function getIndex()
    {
        $unlocksCost = UnlockCost::all();

        return view('unlock-costs.index', compact('unlocksCost'));
    }

    public function postAdd(Request $request)
    {
        $unlockCost = new UnlockCost();
        $unlockCost->network = $request->network;
        $unlockCost->service_id = $request->service_id > 0 ? $request->service_id : null;
        $unlockCost->cost = $request->cost;
        $unlockCost->save();

        return back()->with('messages.success', 'Unlock Cost added');
    }

    public function postUpdate(Request $request)
    {
        $unlockCost = UnlockCost::findOrFail($request->id);
        $unlockCost->network = $request->network;
        $unlockCost->service_id = $request->service_id > 0 ? $request->service_id : null;
        $unlockCost->cost = $request->cost;
        $unlockCost->save();

        return back()->with('messages.success', 'Unlock Cost Updated');
    }

    public function postDelete(Request $request)
    {
        $unlockCost = UnlockCost::findOrFail($request->id);
        $unlockCost->delete();

        return back()->with('messages.success', 'Unlock Cost Removed');
    }
}
