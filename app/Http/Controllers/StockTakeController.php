<?php

namespace App\Http\Controllers;

use App\Exports\ExternalRepairConstExport;
use App\Exports\LostItemsExportCsv;
use App\Exports\MissingItemsTableAllCsv;
use App\Models\Stock;
use App\Models\StockLog;
use App\Models\StockTake;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;


class StockTakeController extends Controller
{
    public function getIndex() {

        $stock=Stock::with('stock_takes')->has('stock_takes')->whereIn('status', ['In Stock',
            'Re-test',
            'Batch',
            'In Repair',
            '3rd Party',
            'Ready for Sale',
            'Retail Stock',
            'Listed on Auction',
            'Reserved for Order',
            'Allocated'])->paginate(config('app.pagination'));

        $stockCount=Stock::with('stock_takes')->has('stock_takes')->whereIn('status', ['In Stock',
            'Re-test',
            'Batch',
            'In Repair',
            '3rd Party',
            'Ready for Sale',
            'Retail Stock',
            'Listed on Auction',
            'Reserved for Order',
            'Allocated'])->count();




        $stockNot=Stock::whereDoesntHave('stock_takes')->whereIn('status', ['In Stock',
            'Re-test',
            'Batch',
            'In Repair',
            '3rd Party',
            'Ready for Sale',
            'Retail Stock',
            'Listed on Auction',
            'Reserved for Order',
            'Allocated'])->paginate(config('app.pagination'));

        $stockNotCount=Stock::whereDoesntHave('stock_takes')->whereIn('status', ['In Stock',
            'Re-test',
            'Batch',
            'In Repair',
            '3rd Party',
            'Ready for Sale',
            'Retail Stock',
            'Listed on Auction',
            'Reserved for Order',
            'Allocated'])->count();








        return view('stock-take.index',compact('stock','stockNot','stockNotCount','stockCount'));
    }

    public function postMarkAsSeen(Request $request) {
        $stockTakeItems = preg_split('/[\s,]+/', $request->stock_take_list, -1, PREG_SPLIT_NO_EMPTY);

        $messages = "";

        if (!count($stockTakeItems)) {
            return back()->with('messages.error', 'Items List is required');
        }
        $total = count($stockTakeItems);
        $found = 0;
        $notFound = 0;
        foreach ($stockTakeItems as $stockTakeItem) {
            $item = Stock::where(function($query) use($stockTakeItem) {
                $query->where('imei', $stockTakeItem);
                $query->orWhere('third_party_ref', $stockTakeItem);
                $query->orWhere('trg_ref', $stockTakeItem);
                $query->orWhere('id', $stockTakeItem);
                if (substr(strtolower($stockTakeItem), 0, 3) == 'rct' && strlen($stockTakeItem) > 3) { // RCT[ID]
                    $query->orWhere('id', substr($stockTakeItem, 3));
                }
            })->first();

            if (!$item && substr($stockTakeItem, 0, 3) == "000" && strlen($stockTakeItem) > 3) {
                $refWithoutZeros = substr($stockTakeItem, 3);
                $item = Stock::where('third_party_ref', $refWithoutZeros)->first();
            }

            if (!$item && substr($stockTakeItem, 0, 4) == "0000" && strlen($stockTakeItem) > 4) {
                $refWithoutZeros = substr($stockTakeItem, 4);
                $item = Stock::where('third_party_ref', $refWithoutZeros)->first();
            }

            if (!$item) {
                $messages .= "$stockTakeItem - Not Found\n";
                $notFound++;
            } else {
                $messages .= "$stockTakeItem - Found\n";
                $found++;

                StockTake::create([
                    'user_id' => Auth::user()->id,
                    'stock_id' => $item->id
                ]);

                StockLog::create([
                    'stock_id' => $item->id,
                    'user_id' => Auth::user()->id,
                    'content' => Auth::user()->first_name . " scanned this item in stock."
                ]);
            }
        }
        $messages = "Stock Take: Found: $found, Not Found: $notFound, Total: $total \n" . $messages;
        return back()->with('messages.info', $messages);
    }

    public function getMissingItems(Request $request) {
        $days = $request->days ?: 7;
        $subDays = Carbon::now()->subDays($days)->startOfDay();
        $itemsFound = StockTake::whereBetween('created_at', [$subDays, Carbon::now()])->get();

        $itemsFoundIds = $itemsFound->pluck('stock_id')->toArray();

        $items = Stock::whereIn('status', [Stock::STATUS_IN_STOCK, Stock::STATUS_READY_FOR_SALE])->whereNotIn('id', $itemsFoundIds)->orderBy('id', 'desc');
        $items = $items->paginate(config('app.pagination'));

        $stats = new \stdClass();
        $stats->batch_purchase_price = Stock::whereIn('status', [Stock::STATUS_BATCH])->sum('purchase_price');
        $stats->batch_count = Stock::whereIn('status', [Stock::STATUS_BATCH])->count();

        $stats->missing_count = Stock::whereIn('status', [Stock::STATUS_IN_STOCK, Stock::STATUS_READY_FOR_SALE])->whereNotIn('id', $itemsFoundIds)->orderBy('id', 'desc')->count();
        $stats->missing_purchase_price = Stock::whereIn('status', [Stock::STATUS_IN_STOCK, Stock::STATUS_READY_FOR_SALE])->whereNotIn('id', $itemsFoundIds)->orderBy('id', 'desc')->sum('purchase_price');

        $stats->found_count = Stock::whereIn('status', [Stock::STATUS_IN_STOCK, Stock::STATUS_READY_FOR_SALE])->whereIn('id', $itemsFoundIds)->count();
        $stats->found_purchase_price = Stock::whereIn('status', [Stock::STATUS_IN_STOCK, Stock::STATUS_READY_FOR_SALE])->whereIn('id', $itemsFoundIds)->sum('purchase_price');

        $totalMissingValue = Stock::whereIn('status', [Stock::STATUS_IN_STOCK, Stock::STATUS_READY_FOR_SALE])->doesntHave('stock_takes')->sum('purchase_price');

        if ($request->ajax()) {
            return response()->json([
                'itemsHtml' => View('stock-take.missing-items-list', compact('items', 'stats', 'totalMissingValue'))->render(),
                'paginationHtml' => $items->appends($request->all())->render()
            ]);
        }

        return view('stock-take.missing-items', compact('items', 'stats', 'totalMissingValue'));
    }

    public function getMissingItemsTableAll(Request $request) {
        return Excel::download(new MissingItemsTableAllCsv($request), 'MissingItems.csv');
    }

    public function getScanner(Request $request) {
        if ($request->ajax() && $request->ref) {
            $message = "<h1><i class='fa fa-times fa-lg'></i></h1>";
            $ref = $request->ref;
            $item = Stock::where(function ($query) use ($ref) {
                $query->where('imei', $ref);
                $query->orWhere('serial', $ref);
                $query->orWhere('third_party_ref', $ref);
                $query->orWhere('id', $ref);
                if (substr(strtolower($ref), 0, 3) == 'rct' && strlen($ref) > 3) { // RCT[ID]
                    $query->orWhere('id', substr($ref, 3));
                }
                if (substr($ref, 0, 3) === '000' && strlen($ref) > 3) {
                    $query->orWhere('third_party_ref', substr($ref, 3));
                }
                if (substr($ref, 0, 4) == '0000' && strlen($ref) > 4) {
                    $query->orWhere('third_party_ref', substr($ref, 4));
                }
            })->first();

            if ($item) {
                StockTake::create([
                    'user_id' => Auth::user()->id,
                    'stock_id' => $item->id
                ]);

                StockLog::create([
                    'stock_id' => $item->id,
                    'user_id' => Auth::user()->id,
                    'content' => Auth::user()->first_name . " scanned this item in stock."
                ]);
                $message = "<h1><i class='fa fa-check fa-lg'></i></h1>";
            }

            return response()->json(['message' => $message]);
        }

        return view('stock-take.scanner');
    }

    public function getMarkAsLost() {
        return view('stock-take.mark-as-lost');
    }

    public function postMarkAsLost(Request $request) {
        $stockItemsList = preg_split('/[\s,]+/', $request->mark_as_lost_list, -1, PREG_SPLIT_NO_EMPTY);

        $messages = "";

        if (!count($stockItemsList)) {
            return back()->with('messages.error', 'Items List is required');
        }
        $total = count($stockItemsList);
        $found = 0;
        $notFound = 0;
        foreach ($stockItemsList as $stockItem) {
            $item = Stock::whereNotIn('status', [Stock::STATUS_SOLD, Stock::STATUS_PAID, Stock::STATUS_RETURNED_TO_SUPPLIER, Stock::STATUS_LOST, Stock::STATUS_DELETED])->where(function($query) use($stockItem) {
                $query->where('imei', $stockItem);
                $query->orWhere('third_party_ref', $stockItem);
                $query->orWhere('trg_ref', $stockItem);
                $query->orWhere('id', $stockItem);
                if (substr(strtolower($stockItem), 0, 3) == 'rct' && strlen($stockItem) > 3) { // RCT[ID]
                    $query->orWhere('id', substr($stockItem, 3));
                }
            })->first();

            if (!$item && substr($stockItem, 0, 3) == "000" && strlen($stockItem) > 3) {
                $refWithoutZeros = substr($stockItem, 3);
                $item = Stock::whereNotIn('status', [Stock::STATUS_SOLD, Stock::STATUS_PAID, Stock::STATUS_RETURNED_TO_SUPPLIER, Stock::STATUS_LOST, Stock::STATUS_DELETED])->where('third_party_ref', $refWithoutZeros)->first();
            }

            if (!$item && substr($stockItem, 0, 4) == "0000" && strlen($stockItem) > 4) {
                $refWithoutZeros = substr($stockItem, 4);
                $item = Stock::whereNotIn('status', [Stock::STATUS_SOLD, Stock::STATUS_PAID, Stock::STATUS_RETURNED_TO_SUPPLIER, Stock::STATUS_LOST, Stock::STATUS_DELETED])->where('third_party_ref', $refWithoutZeros)->first();
            }

            if (!$item) {
                $messages .= "$stockItem - Not Found\n";
                $notFound++;
            } else {
                $messages .= "$stockItem - Marked as Lost\n";
                $found++;

                $item->status = Stock::STATUS_LOST;
                $item->marked_as_lost = Carbon::now();
                $item->lost_reason = $request->lost_reason;
                $item->save();

                StockLog::create([
                    'stock_id' => $item->id,
                    'user_id' => Auth::user()->id,
                    'content' => "This item was removed from stock and marked as lost - $request->lost_reason"
                ]);
            }
        }
        $messages = "Mark as Lost: Found: $found, Not Found: $notFound, Total: $total \n" . $messages;
        return back()->with('messages.info', $messages);
    }

    public function getViewLostItems(Request $request) {
        $query = Stock::query();
        $cont = "This item was removed from stock and marked as lost";
        $query = $query->with(['stockLogs' => function ($query) use ($cont) {
            return $query->where('content', 'not like', '%' . $cont . '%');
        }])->where('status', Stock::STATUS_LOST);

        if ($request->sort) {
            $query->orderBy($request->sort, $request->sortO);
        }
        if ($request->term) {

            $query->query($request->term);
        }

        if ($request->colour) {

            $query->colour($request->colour);
        }
        if ($request->lost_reason) {

            $query->where('lost_reason', $request->lost_reason);
        }
        $items = $query->paginate(config('app.pagination'));

        $itemCount = Stock::where('status', Stock::STATUS_LOST)->count();
        $totalItemValue = Stock::where('status', Stock::STATUS_LOST)->sum('purchase_price');

        if ($request->ajax()) {

            return response()->json([
                'itemsHtml' => View::make('stock-take.view-lost-items-list', compact('items', 'itemCount', 'totalItemValue'))->render(),
                'paginationHtml' => '' . $items->render(),
                'sort' => $request->sort,
                'sortO' => $request->sortO,
            ]);
        }


        return view('stock-take.view-lost-items', compact('items', 'itemCount', 'totalItemValue'));
    }

    public function getViewLostItemsExport() {

        return Excel::download(new LostItemsExportCsv(), 'LostItems.csv');


    }

    public function getViewDeletedItems() {
        $items = Stock::where('status', Stock::STATUS_DELETED)->paginate(config('app.pagination'));

        return view('stock-take.view-deleted-items', compact('items'));
    }

    public function postDeleteAllStockTakeRecords(Request $request) {
        StockTake::orderBy('id', 'desc')->delete();

        return back()->with('messages.success', 'Removed all stock take records');
    }


}
