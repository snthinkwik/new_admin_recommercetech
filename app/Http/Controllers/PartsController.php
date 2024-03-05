<?php

namespace App\Http\Controllers;

use App\Models\Part;
use App\Models\PartLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class PartsController extends Controller
{
    public function getIndex(Request $request) {
        $query = Part::query();

        if ($request->term) {
            $query->where(function($subQuery) use ($request) {
                $subQuery->where('name', 'like', "%$request->term%");
                $subQuery->orWhere('colour', 'like', "%$request->term%");
                $subQuery->orWhere('type', 'like', "%$request->term%");
                $subQuery->orWhere('id', 'like', "%$request->term%");
            });
        }

        if ($request->colour)
            $query->where('colour', $request->colour);

        if ($request->type)
            $query->where('type', $request->type);

        if ($request->sort)
            $query->orderBy($request->sort, $request->sortO);

        $parts = $query->with('suppliers')->paginate(config('app.pagination'));
        if ($request->ajax()) {
            return response()->json([
                'itemsHtml' => View::make('parts.list', compact('parts'))->render(),
                'paginationHtml' => '' . $parts->appends($request->all())->render(),
                'sort' => $request->sort,
                'sortO' => $request->sortO,
            ]);
        }



        return view('parts.index', compact('parts'));
    }

    public function getAdd() {

        return view('parts.add');
    }

    public function postAddOrEdit(Request $request) {
        $part = $request->id ? Part::findOrFail($request->id) : new Part();
        $part->name = $request->name;
        $part->sku = $request->sku;
        $part->colour = $request->colour;
        $part->type = $request->type;
        $part->cost = $request->cost;
        $part->supplier_id = $request->supplier;
        if (isset($request->quantity)) {
            $part->quantity = $request->quantity;
        }

        $part->save();

        if ($request->hasFile('image')) {

            $file = $request->file('image');

            $dir = base_path('public/img/parts/');

            $filename = $part->id . '.' . $file->getClientOriginalExtension();
            Image::make($file)->resize(2048, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            })->save($dir . $filename, 80);
            $part->image = $filename;
            $part->save();
        }

        return redirect()->route('parts')->with('messages.success', 'Done - Part #' . $part->id);
    }

    public function postDelete(Request $request) {
        $part = Part::findOrFail($request->id);
        $part->delete();

        return redirect()->route('parts')->with('messages.success', 'Part has been removed.');
    }

    public function getSingle($id) {
        $part = Part::findOrFail($id);
        return view('parts.add', compact('part'));
    }

    public function getStockLevels() {
        $parts = Part::all();
        return view('parts.stock-levels', compact('parts'));
    }

    public function postUpdateStockLevels(Request $request) {
        $progress = 0;
        foreach ($request->part as $part) {
            $p = Part::find($part['id']);
            if ($p) {
                $p->quantity = $part['quantity'];
                $p->quantity_inbound = $part['quantity_inbound'];
                $changes = "";
                if ($p->quantity != $p->getOriginal('quantity') && !checkUpdatedFields($p->quantity, $p->getOriginal('quantity'))) {
                    $changes .= "Changed RCT Qty from " . $p->getOriginal('quantity') . " to " . $p->quantity . ".\n";
                }
                if ($p->quantity_inbound != $p->getOriginal('quantity_inbound') && !checkUpdatedFields($p->quantity_inbound, $p->getOriginal('quantity_inbound'))) {
                    $changes .= "Changed Inbound Qty from " . $p->getOriginal('quantity_inbound') . " to " . $p->quantity_inbound . ".\n";
                }
                if ($p->quantity_mprc != $p->getOriginal('quantity_mprc') && !checkUpdatedFields($p->quantity_mprc, $p->getOriginal('quantity_mprc'))) {
                    $changes .= "Changed MPRC Qty from " . $p->getOriginal('quantity_mprc') . " to " . $p->quantity_mprc . ".\n";
                }
                if ($p->isDirty() && $changes) {
                    PartLog::create([
                        'part_id' => $p->id,
                        'user_id' => Auth::user()->id,
                        'content' => $changes
                    ]);
                }
                $p->save();
                $progress++;
            }
        }

        return back()->with('messages.success', 'Quantity was successfully updated (' . $progress . '/' . count($request->part) . ' parts).');
    }

    public function getUpdateCosts(Request $request) {
        $query = Part::query();
        if ($request->type)
            $query->where('type', $request->type);

        if ($request->term)
            $query->where('id', 'like', "%$request->term%");
        $query->orWhere('name', 'like', "%$request->term%");


        $parts = $query->get();

        if ($request->ajax()) {
            return response()->json(['itemsHtml' => View::make('parts.update-list', compact('parts'))->render()]);
        }

        return view('parts.update-costs', compact('parts'));
    }

    public function postUpdateCosts(Request $request) {
        if (isset($request['setzero'])) {
            $query = Part::query();
            $query->update(array('quantity' => 0));
        } else {

            $progress = 0;
            foreach ($request->part as $part) {
                $p = Part::find($part['id']);
                if ($p) {
                    $p->quantity = (isset($part['quantity'])) ? $part['quantity'] : '';
                    // $p->quantity_inbound = (isset($part['quantity_inbound'])) ? $part['quantity_inbound'] : '';
                    $p->cost = (isset($part['cost'])) ? $part['cost'] : '';
                    $changes = "";
                    if ($p->quantity != $p->getOriginal('quantity') && !checkUpdatedFields($p->quantity, $p->getOriginal('quantity'))) {
                        $changes .= "Changed RCT Qty from " . $p->getOriginal('quantity') . " to " . $p->quantity . ".\n";
                    }
                    // if($p->quantity_inbound != $p->getOriginal('quantity_inbound') && !checkUpdatedFields($p->quantity_inbound, $p->getOriginal('quantity_inbound'))) {
                    // 	$changes .= "Changed Inbound Qty from ".$p->getOriginal('quantity_inbound')." to ".$p->quantity_inbound.".\n";
                    // }
                    if ($p->cost != $p->getOriginal('cost') && !checkUpdatedFields($p->cost, $p->getOriginal('cost'))) {
                        $changes .= "Changed Cost from " . $p->getOriginal('cost') . " to " . $p->cost . ".\n";
                    }

                    if ($p->quantity_mprc != $p->getOriginal('quantity_mprc') && !checkUpdatedFields($p->quantity_mprc, $p->getOriginal('quantity_mprc'))) {
                        $changes .= "Changed MPRC Qty from " . $p->getOriginal('quantity_mprc') . " to " . $p->quantity_mprc . ".\n";
                    }

                    if ($p->isDirty() && $changes) {
                        PartLog::create([
                            'part_id' => $p->id,
                            'user_id' => Auth::user()->id,
                            'content' => $changes
                        ]);
                    }
                    $p->save();
                    $progress++;
                }
            }

            return back()->with('messages.success', 'Quantity or Cost were successfully updated (' . $progress . '/' . count($request->part) . ' parts).');
        }
    }

    public function getSummary() {

        $totalValueOfParts = 0;
        $totalPartsInStock = 0;
        $parts = Part::all();
        foreach ($parts as $part) {
            $partCount = $part->quantity_inbound + $part->quantity;
            $totalPartsInStock += $partCount;
            $totalValueOfParts += ($partCount * $part->cost);
        }

        $data = new \stdClass();
//        $data->total_value_of_parts = money_format(config('app.money_format'), $totalValueOfParts);
        $data->total_value_of_parts = $totalValueOfParts;
        $data->total_no_parts = $totalPartsInStock;
        return view('parts.summary', compact('data'));
    }

    public function getSearch(Request $request) {

        $parts = Part::where('id', 'like', "%$request->term%")->orWhere('name', 'like', "%$request->term%")->select('id', 'name')->get();


        return response()->json($parts);
    }

}
