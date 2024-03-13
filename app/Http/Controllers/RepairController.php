<?php

namespace App\Http\Controllers;

use App\Commands\Repairs\ExternalImport;
use App\Exports\ExternalRepairConstExport;
use App\Models\Repair;
use App\Models\RepairLog;
use App\Models\RepairsItems;
use App\Models\Stock;
use App\Models\StockLog;
use App\Models\StockPart;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;


class RepairController extends Controller
{
    public function getIndex(Request $request)
    {

        $query = Repair::with('RepaireItem')->orderBy('created_at', 'desc');
        if($request->search_type==="repair_id"){
            $query->where('repair_id', $request->term);
        }
        if($request->term) {
            $query->whereHas('stock', function ($q) use ($request){
                if($request->search_type==="imei"){
                    $q->where('imei', 'like', "%$request->term%");
                }

                if($request->search_type==="serial"){

                    $q->where('serial', 'like', "%$request->term%");
                }


            });
        }

        if($request->status)
        {


            if($request->status !=="all"){
                $query->whereHas('RepaireItem', function ($q) use ($request) {

                    $q->where('status', $request->status);

                });
            }

        }else{


            $query->whereHas('RepaireItem', function ($q) use ($request) {

                $q->where('status', RepairsItems::STATUS_OPEN);

            });
        }


        if($request->engineer)
            $query->where('engineer', $request->engineer);
        if($request->type){
            $query->whereHas('RepaireItem', function ($q) use ($request) {
                $q->where('type', $request->type);
            });

        }
        if($request->start && $request->end){
            $query->whereBetween('created_at',[$request->start,$request->end]);
        }

        $repairs = $query->paginate(config('app.pagination'));


        if ($request->ajax()) {
            return response()->json([
                'itemsHtml' => View::make('repairs.list', compact('repairs'))->render(),
                'paginationHtml' => '' . $repairs->appends($request->all())->render()
            ]);
        }

        return view('repairs.index', compact('repairs'));
    }

    public function getSingle($id)
    {
//		$repair = Repair::findOrFail($id);

        $repairId = $id;
        $repairs = Repair::with('RepaireItemInternal.stock')->findOrFail($id);
        $openCount = RepairsItems::where('repair_id', $id)->where('status', 'Open')->count();
        $closeCount = RepairsItems::where('repair_id', $id)->where('status', 'Close')->count();
        $totalCost = RepairsItems::where('repair_id', $id)->
        select([

            DB::raw("SUM(estimate_repair_cost) as total_estimate_cost"),
            DB::raw("SUM(actual_repair_cost) as total_actual_repair_cost"),
        ])
            ->get();


        return view('repairs.internal_single', compact('repairs', 'openCount', 'closeCount', 'totalCost', 'repairId'));
    }

    public function postSave(Request $request)
    {

        if ($request->repair_id) {
            $repair = Repair::find($request->repair_id);
        } else {

            $repair = new Repair();
        }

        if ($repair->item_id == $request->item_id) {
            return back()->with('messages.error', "This stock already assigned to repair");
        }
        $changes = "Add new Repair By " . Auth::user()->first_name . '' . Auth::user()->last_name;

        $repair->item_id = $request->item_id;
        $repair->status = Stock::STATUS_REPAIR;
        $repair->save();


        RepairLog::create([
            'user_id' => Auth::user()->id,
            'item_id' => $request->item_id,
            'content' => $changes,
        ]);

        return back()->with('messages.success', "Add Repair Successfully ");
    }

    public function getExternalSingle($id)
    {
        $repairId = $id;
        $repairs = Repair::with('RepaireItemExternal.stock')->findOrFail($id);
        $openCount = RepairsItems::where('repair_id', $id)->where('type',RepairsItems::TYPE_EXTERNAL)->where('status', 'Open')->count();
        $closeCount = RepairsItems::where('repair_id', $id)->where('type',RepairsItems::TYPE_EXTERNAL)->where('status', 'Close')->count();
        $totalCost = RepairsItems::where('repair_id', $id)->
        select([

            DB::raw("SUM(estimate_repair_cost) as total_estimate_cost"),
            DB::raw("SUM(actual_repair_cost) as total_actual_repair_cost"),
        ])
            ->get();


        return view('repairs.external_single', compact('repairs', 'repairId',
            'openCount', 'closeCount', 'totalCost'));
    }

    public function postImport(Request $request)
    {

        $this->validate($request, [
            'csv' => 'required|mimes:csv,txt',
        ]);

        $message = '';

        list($rows, $errors) = Repair::parseValidateCsv($request->file('csv'));

        $errors = [];

        foreach ($rows as $row) {

            $value = '';
            if($row['rct_ref'] !== ""){
                if (strpos($row['rct_ref'], "RCT") !== false) {
                    $value = preg_replace('/[^0-9.]+/', '', $row['rct_ref']);

                }
            }



            $query = Stock::orderBy('created_at', 'desc');


            if ($value !== '') {
                $query->where('id', $value);
            } elseif ($row['imei'] !== "") {
                $query->where('imei', $row['imei']);
            } elseif ($row['serial'] !== "") {
                $query->where('serial', $row['serial']);
            }

            $stock = $query->first();

            $repairItem = RepairsItems::where('stock_id', $stock->id)->first();

            if (!in_array($stock->status, [Stock::STATUS_IN_STOCK, Stock::STATUS_READY_FOR_SALE, Stock::STATUS_BATCH])) {

                if ($value !== '') {
                    $message = "RCT " . $value . " Stock Status should be In Stock, Ready for Sale, Batch";
                } else if ($row['imei'] !== "") {
                    $message = "IMEI " . $row['imei'] . " Stock Status should be In Stock, Ready for Sale, Batch";
                } else if ($row['serial'] !== "") {
                    $message = "Serial " . $row['serial'] . " Stock Status should be In Stock, Ready for Sale, Batch";
                }


                array_push($errors, $message);

            }

            if (!is_null($repairItem)) {
                if ($value !== '') {
                    $message = "RCT " . $value . " Already assigned";
                } else {
                    $message = "IMEI/Serial " . $row['imei_serial'] . " Already assigned";
                }
                array_push($errors, $message);
            }


        }
        if (count($errors) > 0) {
            return back()->with("message.m_error", $errors);
        }


        dispatch(new \App\Jobs\Repairs\ExternalImport($rows, $request->repairs_id, Auth::user()->id));

        return back()->with('messages.success', 'successfully run queue refresh page after some time');


    }

    public function getfaults(Request $request)
    {

        $repairsItem = RepairsItems::findOrFail($request->id);

        return ['data' => $repairsItem];
    }

    public function updateRepairCost(Request $request)
    {



        $itemRepair = RepairsItems::find($request->id);

        if ($request->repaired_faults) {
            $itemRepair->repaired_faults = $request->repaired_faults;
        }
        if ($request->estimate_repair_cost) {

            $itemRepair->estimate_repair_cost = $request->estimate_repair_cost;
        }

        if ($request->actual_repair_cost) {

            $itemRepair->actual_repair_cost = $request->actual_repair_cost;


            $currentDate = Carbon::now();
            $newDate = Carbon::createFromFormat('Y-m-d H:i:s', $currentDate->toDateTimeString())->format('d/m/y');
            $repair = Repair::find($itemRepair->repair_id);


            $repairCost = $request->actual_repair_cost;

            $content = $newDate . " External Repair (" . $repair->Repairengineer->name . " ):" . $itemRepair->original_faults . " - " . money_format( $repairCost);

            StockLog::create([
                'user_id' => Auth::user()->id,
                'stock_id' => $itemRepair->stock_id,
                'content' => $content,
            ]);

        }


        $itemRepair->save();


        $stock=Stock::find($itemRepair->stock_id);

        $vatCalation=calculationOfProfit($stock->sale_price,$stock->total_cost_with_repair,$stock->vat_type,$stock->purchase_price);


        $stock->profit=$vatCalation['profit'];
        $stock->true_profit=$vatCalation['true_profit'];
        $stock->marg_vat=$vatCalation['marg_vat'];
        $stock->sale_vat=$vatCalation['sale_vat'];
        $stock->total_price_ex_vat=$vatCalation['total_price_ex_vat'];

        $stock->save();



        return back()->with('messages.success', "SuccessFully Updated Repair Detatils");


    }

    public function getExternalRepairConstExport($id)
    {

        return Excel::download(new ExternalRepairConstExport($id), 'ExternalRepair.csv');

    }

    public function addNewExternalRepair(Request $request)
    {

        if ($request->rct_ref == "" && $request->imei_serial == "") {
            return back()->with('messages.error', 'must be enter value of RCT Ref Or IMEI/Serial Number');
        }

        $value = '';
        if (strpos($request->rct_ref, "RCT") !== false) {
            $value = preg_replace('/[^0-9.]+/', '', $request->rct_ref);

        }

        $query = Stock::orderBy('created_at', 'desc');

        if ($value !== '') {
            $query->where('id', $value);
        } else {
            $query->orWhere('imei', $request->imei_serial)->orWhere('serial', $request->imei_serial);
        }

        $stock = $query->first();



        $stockAvalible = RepairsItems::where('stock_id', $stock->id)->first();
        if (!is_null($stockAvalible)) {
            return back()->with('messages.error', 'This Stock already Assign to Repair');
        }
        $content='';
        if (!is_null($stock)) {
            if (in_array($stock->status, [Stock::STATUS_IN_STOCK, Stock::STATUS_READY_FOR_SALE, Stock::STATUS_BATCH])) {
                $repairItem = new RepairsItems();
                $repairItem->repair_id = $request->repair_id;
                $repairItem->stock_id = $stock->id;
                $repairItem->status = RepairsItems::STATUS_OPEN;
                $repairItem->estimate_repair_cost = $request->estimate_cost;
                $repairItem->actual_repair_cost = $request->actual_cost;
                $repairItem->type = RepairsItems::TYPE_EXTERNAL;
                $repairItem->original_faults = $request->original_faults;
                $repairItem->stock_status = $stock->status;
                $repairItem->save();

                $stock->status = Stock::STATUS_REPAIR;



                $repair = Repair::find($request->repair_id);




                $content .= "Repair Id: " . $request->repair_id . "<br/>";
                $content .= "Type: " . $repairItem->type . "<br/>";
                $content .= "Engineer: " .$repair->Repairengineer->name . "<br/>";
                $content .= "<span class='text-danger'>Original Faults: " . $repairItem->original_faults . "</span><br/>";
                $content .= "Estimate Cost: " .  money_format($repairItem->estimate_repair_cost)  . "<br/>";
                $content .= "<span class='text-success'>Status: " . RepairsItems::STATUS_OPEN . "</span><br/>";


                StockLog::create([
                    'user_id' => Auth::user()->id,
                    'stock_id' => $stock->id,
                    'content' => $content,
                ]);


                $vatCalation=calculationOfProfit($stock->sale_price,$stock->total_cost_with_repair,$stock->vat_type,$stock->purchase_price);

                $stock->profit=$vatCalation['profit'];
                $stock->true_profit=$vatCalation['true_profit'];
                $stock->marg_vat=$vatCalation['marg_vat'];
                $stock->sale_vat=$vatCalation['sale_vat'];
                $stock->total_price_ex_vat=$vatCalation['total_price_ex_vat'];

                $stock->save();



            } else {
                return back()->with('messages.error', "Stock Status should be In Stock, Ready for Sale, Batch");
            }


        }
        return back()->with('messages.success', "SuccessFully Added External Repair Cost");

    }

    public function getTemplate()
    {
        header('Content-Type: application/csv');
        header('Content-Disposition: attachment; filename="External Repairs import.csv"');
        readfile(public_path() . '/files/External_Repairs.csv');
        die;
    }

    public function deleteExternal(Request $request)
    {

        $item = RepairsItems::find($request->id);


        $stock = Stock::find($item->stock_id);



        foreach ($stock->stock_parts as $part){
            $partStock=StockPart::find($part->id);
            $partStock->delete();

        }

        if($item->type===RepairsItems::TYPE_INTERNAL){

            $stock->part_cost=($stock->part_cost-$item->internal_repair_cost);

        }
        $stock->status = $item->stock_status;
        $stock->save();

        $st=Stock::find($item->stock_id);


        if($item->type===RepairsItems::TYPE_INTERNAL){
            $cost=$stock->part_cost;
        }else{
            if($item->actual_repair_cost>0){
                $cost=$item->actual_repair_cost;
            }else{
                $cost=$item->estimate_repair_cost;
            }
        }

        $totalcost=($st->total_cost_with_repair-$cost);



        $calculation= calculationOfProfit($st->sale_price,$totalcost,$st->vat_type,$st->purchase_price);

        $st->profit= $calculation['profit'];
        $st->marg_vat=$calculation['marg_vat'];
        $st->true_profit=$calculation['true_profit'];
        $st->sale_vat=$calculation['sale_vat'];
        $st->total_price_ex_vat=$calculation['total_price_ex_vat'];

        $st->save();

        $item->delete();
        return back()->with('messages.success', "External Repair Successfully Deleted ");

    }

    public function closeRepair(Request $request)
    {


        $repairItems = RepairsItems::findOrFail($request->id);


        if($repairItems->type===RepairsItems::TYPE_EXTERNAL){
            if($repairItems->repaired_faults==""){
                return back()->with('messages.error','Repaired Faults is required');
            }

        }

        $stock = Stock::find($repairItems->stock_id);
        $content='';
        if ($repairItems) {

            $days= $repairItems->created_at->diffInDays(Carbon::now());

            $repairItems->status = RepairsItems::STATUS_CLOSE;
            $repairItems->no_days=$days;

            RepairLog::create([
                'item_id' => $stock->id,
                'content' => "Status changed to Closed"
            ]);
            $repairItems->closed_at = Carbon::now();
            $repairItems->save();

        }

        $stock->status = Stock::STATUS_RE_TEST;

        if($repairItems->type===RepairsItems::TYPE_EXTERNAL){
            $part= "Repaired Faults: " . $repairItems->repaired_faults ;
            $price="Actual Repair Cost: " .money_format($repairItems->actual_repair_cost);

        }else{
            $part= "Parts: " . $repairItems->parts ;
            $price= "Part Cost: " . money_format($stock->part_cost);
        }



        $repair=Repair::find($repairItems->repair_id);
        $content .= "Repair Id: " . $repairItems->repair_id . "<br/>";
        $content .= "Type: " . $repairItems->type . "<br/>";
        $content .= "Engineer: " .$repair->Repairengineer->name . "<br/>";
        $content .= "<span class='text-danger'>".$part. "</span><br/>";
        $content .=  $price."<br/>";
        $content .= "<span class='text-danger'>Status: " . RepairsItems::STATUS_CLOSE . "</span><br/>";




        $stock->save();
        StockLog::create([
            'user_id' => Auth::user()->id,
            'stock_id' => $stock->id,
            'content' => $content
        ]);

        return back()->with('messages.success', "SuccessFully Change Status");


    }

}
