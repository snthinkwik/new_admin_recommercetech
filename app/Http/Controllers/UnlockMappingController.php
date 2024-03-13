<?php

namespace App\Http\Controllers;

use App\Models\UnlockMapping;
use Illuminate\Http\Request;

class UnlockMappingController extends Controller
{
    public function getIndex()
    {
        $unlockMappings = UnlockMapping::get();

        return view('unlock-mapping.index', compact('unlockMappings'));
    }

    public function postDelete(Request $request)
    {
        $unlockMapping = UnlockMapping::findOrFail($request->id);
        $unlockMapping->delete();

        return back()->with('messages.success', 'Removed');
    }

    public function postAdd(Request $request)
    {

        $unlockMapping = new UnlockMapping();
        $unlockMapping->network = $request->network;
        $unlockMapping->service_id = $request->service_id;
        $unlockMapping->make = $request->make;
        $unlockMapping->model = $request->model;
        $unlockMapping->cost = $request->cost;
        $unlockMapping->save();

        return back()->with('messages.success', 'Unlock Mapping Saved');
    }
}
