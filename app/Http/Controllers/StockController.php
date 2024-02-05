<?php

namespace App\Http\Controllers;


use App\Http\Requests\NonSerialisedStockRequest;
use App\Models\Mobicode\GsxCheck;
use App\Models\EbaySaleHistory;
use App\Http\Requests\StockItemRequest;
use App\Http\Requests\StockNewItemRequest;
use App\Models\Stock;
use App\Models\Product;
use App\Models\RepairEngineer;
use App\Models\RepairLog;
use App\Models\RepairsItems;
use App\Models\RepairStatus;
use App\Models\RepairType;
use App\Models\Repair;
use App\Models\Part;
use App\Models\PartLog;
use App\Models\StockPart;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Request as RequestFacade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\StockLog;
use App\Models\ImageProcessing;
use App\Models\SysLog;

class StockController extends Controller
{
    public function getIndex(Request $request)
    {
        if (\Cache::has('stock-users')) {
            \Cache::increment('stock-users');
        } else {
            $expiresAt = \Carbon\Carbon::now()->addMinutes(1);
            \Cache::put('stock-users', 1, $expiresAt);
        }

        if (!$request->exists('status')) {
            $request->merge(['status' => '']);
        }


        if (!$request->exists('unsold')) {
            $request->merge(['unsold' => 'Yes']);
        }

        if (!is_null($request->unsold)) {
            if ($request->unsold === "Yes") {
                $request->merge(['unsold' => '1']);
            } elseif ($request->unsold == 1) {
                $request->merge(['unsold' => '1']);
//                $request->merge(['unsold' => '0']);
            } else {
                $request->merge(['unsold' => '0']);
            }

        }


        session(['stock.last_url' => RequestFacade::fullUrl()]);
        $stock = Stock::fromRequest($request)
            ->paginate(config('app.pagination'))
            ->appends(array_filter($request->all()));


        $items = Stock::fromRequest($request)->groupBy('name', 'capacity', 'grade')
            ->select(DB::raw('count(*) as quantity'), 'name', 'capacity', 'grade')->get();

        $sorting = [
            ['column' => 'name', 'order' => 'asc'],
            ['column' => 'capacity', 'order' => 'asc'],
            ['column' => 'grade', 'order' => 'asc']
        ];
        $items = multiPropertySort($items, $sorting);

        if ($request->ajax()) {
            return response()->json([
                'itemsHtml' => view('stock.items', compact('stock') + ['basket' => Auth::user()->basket])->render(),
                'copyItemsHtml' => view('stock.copy-items-list', compact('items'))->render(),
                'paginationHtml' => '' . $stock->appends($request->all())->render(),
                'sort' => $request->sort,
                'sortO' => $request->sortO,
            ]);
        }

        return view('stock.index', compact('stock', 'items') + ['basket' => Auth::user()->basket]);
    }

    /**
     * @param StockNewItemRequest $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function postAddStock(StockNewItemRequest $request)
    {

        $stock = new Stock();
        $totalCosts = $request->purchase_price;
        $vatType = $request->vat_type;
        $calculations = calculationOfProfit($request->sale_price, $totalCosts, $vatType, $request->purchase_price);

        $stock->make = $request->make;
        $stock->name = $request->name;
        $stock->capacity = $request->capacity;
        $stock->condition = $request->condition;
        $stock->grade = $request->grade;
        $stock->lcd_status = $request->lcd_status;
        $stock->imei = !is_null($request->imei)?$request->imei:'';
        $stock->serial =!is_null($request->serial)?$request->serial:'' ;
        $stock->network = "Unknown";
        $stock->colour = "Mixed";
        $stock->shown_to = Stock::SHOWN_TO_NONE;
        $stock->status = Stock::STATUS_IN_STOCK;
        $stock->third_party_ref = $request->third_party_ref;
        $stock->purchase_date = Carbon::now();
        $stock->product_type = $request->product_type;
        $stock->purchase_order_number = $request->purchase_order_number;
        $stock->purchase_price = $request->purchase_price;
        $stock->sale_price = $request->sale_price;
        $stock->vat_type = $request->vat_type;
        $stock->product_id = $request->product_id;
        $stock->original_grade = $request->grade;
        $stock->original_condition = $request->condition;
        $stock->ps_model = $request->ps_model;
        $stock->supplier_id = $request->supplier_name;


        if(!is_null($request->sim_lock_check)){
            $stock->network='Check Not Req';
        }
        $stock->profit = $calculations['profit'];
        $stock->marg_vat = $calculations['marg_vat'];
        $stock->true_profit = $calculations['true_profit'];
        $stock->sale_vat = $calculations['sale_vat'];
        $stock->total_price_ex_vat = $calculations['total_price_ex_vat'];

        $stock->save();


        $checkIMEI='';
        if (!empty($stock->imei)) {
            $checkIMEI=$stock->imei;
        }else{
            $checkIMEI=$stock->serial;
        }

        if(is_null($request->sim_lock_check)){

            $url = "https://alpha.imeicheck.com/api/php-api/create?key=". config('services.imei_check_api_key'). "&service=".config('services.imei_check_service_code')."&imei=".$checkIMEI;
            $header = array(
                'Accept: application/json',
                'Content-Type: application/x-www-form-urlencoded',

            );

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);

            $result = curl_exec($curl);

            if (!$result) {
                die("Connection Failure");
            }

            curl_close($curl);


            $data = json_decode($result);

            $stockNetworkUpdate=Stock::find($stock->id);


            if($data->status==="success"){


                if($data->object->simlock){
                        $stockNetworkUpdate->network=Stock::NETWORK_SIM_LOCKERD;
                    }else{
                        $stockNetworkUpdate->network=Stock::NETWORK_CHECK_UNLOCKED;
                    }


            }else{
                $stockNetworkUpdate->network=Stock::NETWORK_NO_RESULT;
            }

            $stockNetworkUpdate->save();
        }



        if (!empty($stock->imei)) {
            artisan_call_background("phone-check:create-checks $stock->imei");
        } else {
            artisan_call_background("phone-check:create-checks $stock->serial");
        }




        return redirect()->route('stock.single', ['id' => $stock->id]);
    }

    /**
     * @param StockNewItemRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postSave(Request $request)
    {


        $flag = 0;
        $item = $request->id ? Stock::findOrFail($request->id) : new Stock;


        $options = [];
        $oldSku = $item->sku;
        $requestedSku = $request->sku;
        $oldShownTo = $item->shown_to;



        if ($item->exists && $request->shown_to != $oldShownTo
            && in_array($request->shown_to, [Stock::SHOWN_TO_EBAY, Stock::SHOWN_TO_EBAY_AND_SHOP])
            && !in_array($item->grade, [Stock::GRADE_FULLY_WORKING, Stock::GRADE_FULLY_WORKING_NO_TOUCH_ID])
        ) {
            return back()->with('messages.error', "Only Fully Working and Fully Working - No Touch ID items can be shown to eBay or eBay & Shop");
        }

        if ($item->grade != $request->grade && $request->grade == Stock::GRADE_FULLY_WORKING && !$item->grade_fully_working_available_) {
            $override = "";
            if (in_array(Auth::user()->admin_type, ['admin'])) {
                $override = \BsForm::open(['method' => 'post', 'route' => 'stock.change-grade-fully-working']);
                $override .= \BsForm::hidden('id', $item->id);
                $override .= \BsForm::submit('Click here to override');
                $override .= \BsForm::close();
            }
            return back()->with('messages.error-custom', "Battery life is $item->battery_life% and therefore this phone cannot be sold as fully working. Please replace the battery and then re-run through Phone Diagnostics. " . $override);
        }


        if (in_array($item->status, [Stock::STATUS_SOLD, Stock::STATUS_PAID])) {

            $repairCost = 0;
            if (count($item->repair_item) > 0) {

                foreach ($item->repair_item as $repair) {
                    if ($repair->type === RepairsItems::TYPE_EXTERNAL) {
                        $repairCost = $repair->actual_repair_cost < 1 ? $repair->estimate_repair_cost : $repair->actual_repair_cost;
                    }
                }

            }
            $totalCosts = $repairCost + $request->purchase_price + $item->unlock_cost + $item->part_cost;

            if ($request->vat_type === "Standard" && $request->sale_price) {

                $calculations = calculationOfProfit($request->sale_price, $totalCosts, $request->vat_type);
                $item->sale_vat = $calculations['sale_vat'];
                $item->total_price_ex_vat = $calculations['total_price_ex_vat'];
                $item->profit = $calculations['profit'];
                $item->true_profit = $calculations['true_profit'];
                $item->marg_vat = null;


            } else if ($request->sale_price) {

                $calculations = calculationOfProfit($request->sale_price, $totalCosts, $request->vat_type, $request->purchase_price);
                $item->profit = $calculations['profit'];
                $item->marg_vat = $calculations['marg_vat'];
                $item->true_profit = $calculations['true_profit'];
                $item->sale_vat = $calculations['sale_vat'];
                $item->total_price_ex_vat = $calculations['total_price_ex_vat'];

            }
        } else {

            if ($request->vat_type === "Standard" && $request->sale_price) {

                $totalCosts = $item->total_cost_with_repair;
                $calculations = calculationOfProfit($request->sale_price, $totalCosts, $request->vat_type);

                $item->sale_vat = $calculations['sale_vat'];
                $item->total_price_ex_vat = $calculations['total_price_ex_vat'];
                $item->profit = $calculations['profit'];
                $item->true_profit = $calculations['true_profit'];
                $item->marg_vat = null;


            } else if ($request->sale_price) {

                $totalCosts = $item->total_cost_with_repair;
                $calculations = calculationOfProfit($request->sale_price, $totalCosts, $request->vat_type, $request->purchase_price);
                $item->profit = $calculations['profit'];
                $item->marg_vat = $calculations['marg_vat'];
                $item->true_profit = $calculations['true_profit'];
                $item->sale_vat = $calculations['sale_vat'];
                $item->total_price_ex_vat = $calculations['total_price_ex_vat'];

            }
        }

        if ($item->exists && $oldSku != $requestedSku && !$item->manual_sku && $request->sku) {
            $options = ['avoid_sku_update' => 1];
            $item->manual_sku = true;
        }


        $item->fill($request->all());
        /*if($item->exists && $oldShownTo != $request->shown_to && !in_array($oldShownTo, [Stock::SHOWN_TO_EBAY_AND_SHOP, Stock::SHOWN_TO_EBAY]) && in_array($request->shown_to, [Stock::SHOWN_TO_EBAY, Stock::SHOWN_TO_EBAY_AND_SHOP])) {
            $item->grade = Stock::GRADE_FULLY_WORKING;
        }*/
        if ($request->faults) {
            $faults = $request->faults;
            $faults = array_diff($faults, [0]); // only faults set as Yes
            $item->faults = $faults;
        }

        if ($request->status == Stock::STATUS_IN_STOCK && $item->status == Stock::STATUS_READY_FOR_SALE) {
            $item->status = $request->status;
        } else {
            $item->status = $request->status;
        }

        if ($item->status == Stock::STATUS_RETAIL_STOCK && $request->new_sku) {
            $item->new_sku = $request->new_sku;
        }


        if ($request->id) {
            $stockFinal=Stock::findOrFail($request->id);


//            if(!is_null($stockFinal)){
//
//                if($request->supplier_id !=='' ){
//
//
//
//
//
//                    if($request->supplier_id != $stockFinal->supplier_id){
//
//                        $supplier=Supplier::find($request->supplier_id);
//                        $arr = (array) json_decode($supplier->select_grade);
//                        foreach ($arr as $key=>$value){
//                            $original=[
//                               'supplier_g'=>$key,
//                               'grade'=>$value
//                           ];
//                        }
//                           $stockFinal->original_condition=json_encode($original);
//                           $stockFinal->save();
//                    }
//
//                }
//            }

        }


        if ($request->exists('supplier_id'))
            $item->supplier_id = $request->supplier_id ? $request->supplier_id : null;

//		if($request->apple_case_id)
//			$item->apple_case_id = $request->apple_case_id;
        if ($request->show_warranty != null)
            $item->show_warranty = $request->show_warranty;
        if ($request->purchase_country) {
            $item->purchase_country = $request->purchase_country;
        }

        if ($item->getOriginal('status') == Stock::STATUS_BATCH && $item->getOriginal('status') != $item->status) {
            $item->batch_id = null;
            $item->locked_by = '';
        }

        if ($item->exists && $item->isDirty()) {
            $changes = "";
            foreach ($item->getAttributes() as $key => $value) {
                if ($value !== $item->getOriginal($key) && !$this->checkUpdatedFields($value, $item->getOriginal($key))) {
                    $changes .= "Changed \"$key\" from \"{$item->getOriginal($key)}\" to \"$value\".\n";
                }
            }
            if ($changes) {
                StockLog::create([
                    'user_id' => Auth::user()->id,
                    'stock_id' => $item->id,
                    'content' => $changes,
                ]);
            }
        }

        $item->serial=!is_null($item->serial)?$item->serial:'';
        $item->save($options);

        return back()->with('messages.success', "Stock item saved.");
    }

    /**
     * @param $id
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\JsonResponse
     */
    public function getSingle($id, Request $request)
    {
        $item = Stock::findOrFail($id);


        if ($request->test)
            dd($item->getRepairsAndParts());
        $logs = StockLog::where('stock_id', $item->id)->orderBy('id', 'desc')->paginate(10);
        $ebaySalesHistory = EbaySaleHistory::where('stock_id', $id)->orderBy('id', 'desc')->paginate(10);

        if ($request->term && $request->ajax()) {


            $products = Product::select('id', 'product_name', 'slug')->where(function ($query) use ($request) {
                $query->where('product_name', 'like', '%' . $request->term . '%')
                    ->orWhere('slug', 'like', '%' . $request->term . '%')
                    ->orWhere('id', 'like', '%' . $request->term . '%');
            })->where('products.archive', '0')->get();


            return response()->json($products);
        }

        // Store users in cache to count no of logged users
        if (\Cache::has('stock-item-' . $item->id)) {
            \Cache::increment('stock-item-' . $item->id);
        } else {
            $expiresAt = \Carbon\Carbon::now()->addMinutes(1);
            \Cache::put('stock-item-' . $item->id, 1, $expiresAt);
        }


        $next = Stock::where('status', Stock::STATUS_IN_STOCK)->where('shown_to', Stock::SHOWN_TO_ALL)->where('id', '>', $item->id)->first();
        if (!$next)
            $next = Stock::where('status', Stock::STATUS_IN_STOCK)->where('shown_to', Stock::SHOWN_TO_ALL)->where('id', '<', $item->id)->first();

        // start -- get repair type engineer and status

        $repairLogs = RepairLog::where('item_id', $id)->get();

        $repairsInternalCount = RepairsItems::where('stock_id', $id)->where('type', RepairsItems::TYPE_INTERNAL)->count();
        $repairsExternalCount = RepairsItems::where('stock_id', $id)->where('type', RepairsItems::TYPE_EXTERNAL)->count();


        $types = RepairType::all();
        $repairTypes = [];
        $repairTypes[''] = 'Select Type';
        foreach ($types as $type) {
            $repairTypes[$type->id] = $type->name;
        }

        $statuses = RepairStatus::all();
        $repairStatus = [];
        foreach ($statuses as $status) {
            $repairStatus[$status->id] = $status->name;
        }

        $engineers = RepairEngineer::all();
        $repairEngineers = [];
        $repairEngineers[''] = 'Select Engineer';
        foreach ($engineers as $engineer) {
            $repairEngineers[$engineer->id] = $engineer->name;
        }
        // stop -- get repair type engineer and status
        return view('stock.single', compact('item', 'logs', 'next', 'repairsInternalCount', 'repairsExternalCount', 'repairTypes', 'repairStatus', 'repairEngineers', 'repairLogs', 'ebaySalesHistory') + ['basket' => Auth::user()->basket]);
    }

    /**
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function phoneCheck($id)
    {
        $stock = Stock::findOrFail($id);
        if (!empty($stock->imei)) {
            artisan_call_background("phone-check:create-checks $stock->imei");

        } else {
            artisan_call_background("phone-check:create-checks $stock->serial");
        }

        return back()->with('messages.success', 'Phone check Cron job run');
    }


    /**
     * @param $current
     * @param $original
     * @return bool
     */
    protected function checkUpdatedFields($current, $original)
    {
        if ($original == null && $current == '') {
            return true;
        }

        if (!is_numeric($current) || !is_numeric($original)) {
            return false;
        }

        // If one is numeric and one is float, e.g 5 and 5.00
        if (
            (strpos($original, '.') !== false || strpos($current, '.') !== false) &&
            (strpos($original, '.') === false || strpos($current, '.') === false)
        ) {
            if (strpos($original, '.') === false) $original .= '.00';
            if (strpos($current, '.') === false) $current .= '.00';
        }

        return strcmp((string)$current, (string)$original) === 0;
    }

    /**
     * @return void
     */
    public function getTemplate()
    {

        header('Content-Type: application/csv');
        header('Content-Disposition: attachment; filename="Stock import.csv"');
        readfile(public_path() . '/files/Stock import.csv');
        die;
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function postImport(Request $request)
    {


        $csv = $request->file('csv');
        if (empty($csv)) {
            return back()->withInput()->with('messages.error', "Please upload the CSV file.");
        }

        if ($csv->getClientOriginalExtension() != 'csv') {
            return back()->with('messages.error', 'Invalid file extension');
        }

        list($rows, $errors) = Stock::parseValidateCsv($csv);

        if ($errors) {
            return back()->withInput()->with('stock.csv_errors', $errors);
        }

        //dd($request->vat_type );

        $stock = Stock::where('purchase_order_number', $rows[0]['purchase_order_number'])->first();
        if (!is_null($stock)) {
            return back()->with('messages.error', 'Already Assign Purchase Order Number');
        }


        foreach ($rows as $row) {


            $item = new Stock($row);
            if ($request->supplier_id) {
                $item->supplier_id = $request->supplier_id;
                $item->original_condition = $row['condition'];
            }

            $item->vat_type = $request->vat_type;
            $item->ps_model = $request->ps_model;
            $item->original_grade = $row['grade'];

            if($row['dont_sim_lock_check']==="Yes" && $row['network'] ===''){
                $item->network='Check Not Req';

            }
//            $item->original_condition=$row['condition'];
            $item->status = $request->mark_in_stock ? Stock::STATUS_IN_STOCK : Stock::STATUS_INBOUND;

            if ($request->vat_type === "Standard" && $row['sale_price'] && $row['purchase_price']) {
                $total_price_ex_value = ($row['sale_price'] / 1.2);
                $vat = ($row['sale_price'] - $total_price_ex_value);
                $item->sale_vat = $vat;
                $item->total_price_ex_vat = $total_price_ex_value;
                $item->profit = $total_price_ex_value - $row['purchase_price'];
                $item->true_profit = $total_price_ex_value - $row['purchase_price'];
                $item->marg_vat = null;

            } elseif ($row['sale_price'] && $row['purchase_price']) {

                $margVat = (($row['sale_price'] - $row['purchase_price']) * 0.1667);
                $item->profit = ($row['sale_price'] - $row['purchase_price']);
                $item->marg_vat = $margVat;
                $item->true_profit = ($row['sale_price'] - $row['purchase_price']) - $margVat;
                $item->sale_vat = null;
                $item->total_price_ex_vat = null;
            }

            $item->save();

            $checkIMEI='';
            if (!empty($row['imei'])) {
                $checkIMEI=$row['imei'];
            }else{
                $checkIMEI=$row['serial'];
            }

            if($row['dont_sim_lock_check']==="No"){
                $client = new Client();
                $response = $client->get("https://alpha.imeicheck.com/api/php-api/create?key=". config('services.imei_check_api_key'). "&service=".config('services.imei_check_service_code')."&imei=".$checkIMEI);
                $data = $response->json();
                $stockNetworkUpdate=Stock::find($item->id);
                if($data['status']==="success"){
                    if(isset($data['object']['simlock'])){
                        if($data['object']['simlock']){
                            $stockNetworkUpdate->network=Stock::NETWORK_SIM_LOCKERD;
                        }else{
                            $stockNetworkUpdate->network=Stock::NETWORK_CHECK_UNLOCKED;
                        }
                    }

                }else{
                    $stockNetworkUpdate->network=Stock::NETWORK_NO_RESULT;
                }

                $stockNetworkUpdate->save();

                StockLog::create([
                    'user_id' => Auth::user()->id,
                    'stock_id' => $item->id,
                    'content' => "Update Network:-". $stockNetworkUpdate->network,
                ]);
            }

            StockLog::create([
                'user_id' => Auth::user()->id,
                'stock_id' => $item->id,
              //  'content' => "Imported. Purchase Price: " . money_format(config('app.money_format'), $item->purchase_price),
                'content' => "Imported. Purchase Price: " . $item->purchase_price,
            ]);
            if ($item->make == 'Samsung') {
                GsxCheck::create([
                    'user_id' => Auth::user()->id,
                    'stock_id' => $item->id,
                    'imei' => $item->imei,
                    'status' => GsxCheck::STATUS_NEW
                ]);
                StockLog::create([
                    'user_id' => Auth::user()->id,
                    'stock_id' => $item->id,
                    'content' => "Samsung Device Imported - Check Created.",
                ]);
            }
        }


        artisan_call_background("call-phone-check-imei");


        return back()->with('messages.success', 'Stock items added.');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postSaveRepair(Request $request)
    {


        $repair = new Repair();

        $repair->item_id = $request->item_id;
        $repair->type = $request->type;
        $repair->engineer = $request->engineer;
        $repair->status = $request->status;
        $repair->save();

        $stock = Stock::find($request->item_id);

        $repairItem = new RepairsItems();
        $repairItem->repair_id = $repair->id;
        $repairItem->stock_id = $request->item_id;
        $repairItem->type = RepairsItems::TYPE_INTERNAL;
        $repairItem->status = RepairsItems::STATUS_OPEN;
        $repairItem->stock_status = $stock->status;
        $repairItem->original_faults = $stock->phone_check ? $stock->phone_check->report_failed_render : '';
        $repairItem->total_repair_cost=0;
        $repairItem->save();


        $content = '';
        $content .= "Repair Id: " . $repair->id . "<br/>";
        $content .= "Type: " . $repairItem->type . "<br/>";
        $content .= "Engineer: " . $repair->Repairengineer->name . "<br/>";
        $content .= "Estimate Cost: " . $repairItem->estimate_repair_cost . "<br/>";
        $content .= "<span class='text-success'>Status: " . RepairsItems::STATUS_OPEN . "</span><br/>";


        StockLog::create([
            'stock_id' => $request->item_id,
            'user_id' => Auth::user()->id,
            'content' => $content
        ]);


        return back()->with('messages.success', "Repair created successfully.");
    }


    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postPartsAdd(Request $request)
    {
        if (!count($request->parts)) {
            return back()->with('messages.error', "No Parts to add");
        }

        $stock = Stock::findOrFail($request->stock_id);
        $parts = Part::whereIn('id', array_keys($request->parts))->get();

        $repairItem = RepairsItems::where('stock_id', $request->stock_id)->where('type', RepairsItems::TYPE_INTERNAL)->first();

        if (!count($parts)) {
            return back()->with('messages.error', 'No Parts Found');
        }

        $changes = "Assigned Parts:\n";

        foreach ($parts as $part) {

            $old = $stock->part_cost_formatted;
            $changes .= "$part->name,";
            $stockUrl = route('stock.single', ['id' => $stock->id]);
            $part->quantity = $part->quantity - 1;
            $part->save();
            PartLog::create([
                'user_id' => Auth::user()->id,
                'part_id' => $part->id,
                'content' => 'Part Used in Stock Item <a href="' . $stockUrl . '">#' . $stock->id . '</a> RCT Qty updated'
            ]);
            $stock->part_cost += $part->cost;
            $stock->save();
            $changes .= " Part Cost Updated. Old: $old | New: $stock->part_cost_formatted\n";
            //StockPart::create(['part_id' => $part->id, 'stock_id' => $stock->id]);
            $stock->parts()->attach($part, ['cost' => $part->cost, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        }


        StockLog::create([
            'stock_id' => $stock->id,
            'user_id' => Auth::user()->id,
            'content' => $changes
        ]);

        $repairItem->internal_repair_cost = $stock->part_cost;

        $repairItem->save();

        //    dd($stock->total_cost_with_repair);
        /*$parts = $parts->lists('id');
        $stock->parts()->sync($parts, false);*/


        $vatCalation = calculationOfProfit($stock->sale_price, $stock->total_cost_with_repair, $stock->vat_type, $stock->purchase_price);


        $stock->profit = $vatCalation['profit'];
        $stock->true_profit = $vatCalation['true_profit'];
        $stock->marg_vat = $vatCalation['marg_vat'];
        $stock->sale_vat = $vatCalation['sale_vat'];
        $stock->total_price_ex_vat = $vatCalation['total_price_ex_vat'];

        $stock->save();


        $currentDate = Carbon::now();
        $newDate = Carbon::createFromFormat('Y-m-d H:i:s', $currentDate->toDateTimeString())->format('d/m/y');
        $repair = Repair::find($repairItem->repair_id);

       // $content = $newDate . "Internal Repair (" . $repair->Repairengineer->name . " ):" . $repairItem->parts . " - " . money_format(config('app.money_format'), $stock->part_cost);
        $content = $newDate . "Internal Repair (" . $repair->Repairengineer->name . " ):" . $repairItem->parts . " - " . $stock->part_cost;

        StockLog::create([
            'user_id' => Auth::user()->id,
            'stock_id' => $stock->id,
            'content' => $content,
        ]);


        return back()->with('messages.success', 'Parts have been added to Stock');

    }


    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postPartsRemove(Request $request)
    {
        $stock = Stock::findOrFail($request->stock_id);

        $stockPart = StockPart::findOrFail($request->stock_part_id);
        $part = $stockPart->part;

        $cost = $stockPart->part_cost;

        $stockPart->delete();

        $stock = $stock->fresh();
        $old = $stock->part_cost_formatted;
        $stock->part_cost -= $cost;


        $vatCalation = calculationOfProfit($stock->sale_price, $stock->total_cost_with_repair, $stock->vat_type, $stock->purchase_price);


        $stock->profit = $vatCalation['profit'];
        $stock->true_profit = $vatCalation['true_profit'];
        $stock->marg_vat = $vatCalation['marg_vat'];
        $stock->sale_vat = $vatCalation['sale_vat'];
        $stock->total_price_ex_vat = $vatCalation['total_price_ex_vat'];


        $stock->save();

        $part->quantity += 1;
        $part->save();
        $stockUrl = route('stock.single', ['id' => $stock->id]);
        PartLog::create([
            'user_id' => Auth::user()->id,
            'part_id' => $part->id,
            'content' => 'Part removed from Stock Item <a href="' . $stockUrl . '">#' . $stock->id . '</a> RCT Qty updated'
        ]);

        $repairItem = RepairsItems::where('stock_id', $request->stock_id)->where('type', RepairsItems::TYPE_INTERNAL)->first();
        $repairItem->internal_repair_cost = $stock->part_cost;
        $repairItem->save();

        $changes = "Part $part->name removed from Stock Item. Part Cost updated. Old: $old | New: $stock->part_cost_formatted";

        StockLog::create([
            'stock_id' => $stock->id,
            'user_id' => Auth::user()->id,
            'content' => $changes
        ]);

        return back()->with('messages.success', 'Part removed, Purchase Price has been updated');
    }


    public function addExternalRepairCost(Request $request)
    {

        if ($request->repair_item_id) {
            $repairItem = RepairsItems::find($request->repair_item_id);
        } else {

            $repairItem = new RepairsItems();
        }

        $repairItem->repair_id = $request->id;
        $repairItem->type = RepairsItems::TYPE_EXTERNAL;
        $repairItem->status = "Open";
        $repairItem->stock_id = $request->item_id;
        $repairItem->estimate_repair_cost = $request->estimate_repair_cost;
        $repairItem->total_repair_cost=0;
        $repairItem->save();

        $repair = Repair::findOrFail($repairItem->repair_id);

        $content = '';

        $content .= "Repair Id: " . $repairItem->repair_id . "<br/>";
        $content .= "Type: " . $repairItem->type . "<br/>";
        $content .= "Engineer: " . $repair->Repairengineer->name . "<br/>";
        $content .= "<span class='text-danger'>Original Faults: " . $repairItem->original_faults . "</span><br/>";
        $content .= "Estimate Cost: " . $repairItem->estimate_repair_cost . "<br/>";
        $content .= "<span class='text-success'>Status: " . RepairsItems::STATUS_OPEN . "</span><br/>";

        StockLog::create([
            'user_id' => Auth::user()->id,
            'stock_id' => $request->item_id,
            'content' => $content,
        ]);


        return back()->with('messages.success', 'SuccessFull Add External Repair Cost');


    }

    public function postChangeProductType(Request $request)
    {
        $item = Stock::findOrFail($request->id);

        $item->product_type = $request->product_type;

        if ($item->exists && $item->isDirty()) {
            $changes = "";
            foreach ($item->getAttributes() as $key => $value) {
                if ($value !== $item->getOriginal($key) && !$this->checkUpdatedFields($value, $item->getOriginal($key))) {
                    $changes .= "Changed \"$key\" from \"{$item->getOriginal($key)}\" to \"$value\".\n";
                }
            }
            if ($changes) {
                StockLog::create([
                    'user_id' => Auth::user()->id,
                    'stock_id' => $item->id,
                    'content' => $changes,
                ]);
            }
        }
        $item->save();

        return back()->with('messages.success', 'Product Type Updated');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function uploadProcessingImage(Request $request)
    {

        $allowedfileExtension = ['jpeg', 'jpg', 'png', 'PNG'];
        if ($request->hasFile('image')) {
            foreach ($request->file('image') as $key => $image) {
                $extension = $image->getClientOriginalExtension();

                $check = in_array($extension, $allowedfileExtension);
                if (!$check) {
                    return back()->with('messages.error', 'Only Upload Png,Jpeg,Jpg');
                }
            }

        }
        if ($request->hasFile('image')) {
            foreach ($request->file('image') as $key => $image) {
                $dir = base_path('public/img/processing-image/');
                $randomId = rand(10, 10000);
                $filename = $randomId . "." . $image->getClientOriginalExtension();
                $image->move($dir, $filename);
                $image = new ImageProcessing();
                $image->stock_id = $request->stock_id;
                $image->image_path = $filename;
                $image->save();
            }

        }
        return back()->with("messages.success", 'image successfully upload');
    }

    /**
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function removeProcessingImage($id)
    {
        $image =ImageProcessing::find($id);
        $image->delete();

        return back()->with("messages.success", 'image successfully deleted');


    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postRemoveFromBatch(Request $request)
    {
        $stock = Stock::findOrFail($request->stock);

        $stock->status = Stock::STATUS_IN_STOCK;
        $stock->locked_by = '';
        $stock->batch_id = null;
        $stock->save();

        return back()->with('messages.success', '#' . $stock->our_ref . " was successfully returned to stock.");
    }

    /**
     * @param $option
     * @param $query
     * @param $columns
     * @return void
     */
    public function getExport($option = null, $query = null, $columns = null)
    {

        ini_set('max_execution_time', 600);
        if (Auth::user()->type !== "admin") {
            $option = "customer";
        }

        if ($option) {
            if ($option == "in_stock") {
                $query = ['status' => \App\Stock::STATUS_IN_STOCK, 'shown_to' => Stock::SHOWN_TO_NONE];
            } elseif ($option == "for_sale") {
                $query = ['status' => Stock::STATUS_IN_STOCK, 'shown_to' => Stock::SHOWN_TO_ALL];
            } elseif ($option == "customer") {
                $query = ['status' => Stock::STATUS_IN_STOCK, 'shown_to' => Stock::SHOWN_TO_ALL];
                $columns = [
                    'Ref' => 'our_ref',
                    'Device' => 'name',
                    'Capacity' => 'capacity',
                    'Network' => 'network',
                    'IMEI / Serial' => 'imei',
                    'Serial' => 'serial',
                    'Sales Price' => 'sale_price_formatted',
                    'Non OEM Parts' => 'oem_parts',
                    'VAT Type' => 'vat_type'

                ];
            } elseif ($option == "ebay") {
                $query = ['status' => Stock::STATUS_IN_STOCK, 'shown_to' => Stock::SHOWN_TO_EBAY];
            } elseif ($option == "epos") {
                $query = ['status' => Stock::STATUS_IN_STOCK, 'shown_to' => Stock::SHOWN_TO_EBAY_AND_SHOP];
            } elseif ($option == "ebay_auction") {
                $query = ['status' => Stock::STATUS_IN_STOCK, 'shown_to' => Stock::SHOWN_TO_EBAY_AUCTION];
            } elseif ($option == 'inbound') {
                $query = ['status' => Stock::STATUS_INBOUND];
                $columns = [
                    'Ref' => 'our_ref',
                    '3rd-party ref' => 'third_party_ref',
                    'IMEI / Serial' => 'imei',
                    'Name' => 'name',
                    'Capacity' => 'capacity',
                    'Colour' => 'colour',
                    'Condition' => 'condition',
                    'Grade' => 'grade',
                    'Network' => 'network',
                    'Status' => 'status',
                    'Cracked back' => 'cracked_back',
                    'VAT Type' => 'vat_type',
                    'Supplier' => 'supplier_name',
                    'Sales price' => 'sale_price_formatted',
                    'Purchase Order Number' => 'purchase_order_number',
                    'Purchase date' => 'purchase_date',
                    //'Purchase price' => 'total_costs_formatted',
                    'Purchase price' => 'total_cost_with_repair_formatted',
                    'Purchase order Ref' => 'purchase_order_number',
                    'Location' => 'location',
                    'Engineer Notes' => 'notes',
                    'Non OEM Parts' => 'oem_parts',
                ];
            }
        }
        if ($columns) {
            $fields = $columns;
        } else {
            $fields = [
                'Ref' => 'our_ref',
                'Sku' => 'sku',
                '3rd-party ref' => 'third_party_ref',
                'IMEI / Serial ' => 'imei',
                'Country' => 'purchase_country',
                'Name' => 'name',
                'Capacity' => 'capacity',
                'Colour' => 'colour',
                'Condition' => 'condition',
                'Grade' => 'grade',
                'Network' => 'network',
                'Status' => 'status',
                'Cracked back' => 'cracked_back',
                'VAT Type' => 'vat_type',
                'Supplier' => 'supplier_name',
                'Purchase date' => 'purchase_date',
                'Product Purchase Price' => 'purchase_price_formatted',
                // 'Purchase price' => 'total_costs_formatted',
                'Purchase price' => 'total_cost_with_repair_formatted',
                'Purchase order Ref' => 'purchase_order_number',
                'Sold date' => 'sold_at',
                'Sales price' => 'sale_price_formatted',
                'GP' => 'gross_profit_formatted',
                'GP%' => 'gross_profit_percentage_formatted',
                'VAT Margin' => 'vat_formatted',
                'Total GP' => 'total_gross_profit_formatted',
                'Total GP%' => 'total_gross_profit_percentage_formatted',
                'Customer ID' => 'customer_id',
                'Customer Name' => 'customer_name',
                'Buyers Ref' => 'buyers_ref',
                'Location' => 'location',
                'No. Tests' => 'phone_check_updates',
                'Engineer Notes' => 'notes',
                'Non OEM Parts' => 'oem_parts',
                'Unlocked?' => 'unlocked_from_network',
                'Repair 1 Date' => 'repair_1_date',
                'Repair 1' => 'repair_1_parts',
                'Retest 1' => 'repair_1_retest',
                'Repair 2 Date' => 'repair_2_date',
                'Repair 2' => 'repair_2_parts',
                'Retest 2' => 'repair_2_retest',
                'Repair 3 Date' => 'repair_3_date',
                'Repair 3' => 'repair_3_parts',
                'Retest 3' => 'repair_3_retest',
            ];
        }

        $csvPath = tempnam('/tmp', 'stock-full-export-');
        unlink($csvPath);
        $csvPath .= '.csv';
        $fh = fopen($csvPath, 'w');
        fputcsv($fh, array_keys($fields));

        if ($query) {
            $q = Stock::query();
            foreach ($query as $key => $value) {
                $q->where($key, 'like', $value);
            }
            $q->orderBy('id', 'desc');
            $q->chunk(500, function ($stock) use ($fields, $fh) {
                foreach ($stock as $item) {
                    $oemPart = '';
                    if (!is_null($item->oem_parts)) {
                        foreach (json_decode($item->oem_parts) as $oem_part) {

                            $oemPart .= $oem_part->name . ',';
                        }
                    }


                    $item->oem_parts = $oemPart;

                    $item->imei = $item->imei !== "" ? $item->imei : $item->serial;
                    $row = array_map(function ($field) use ($item) {
                        return $item->$field;
                    }, $fields);
                    fputcsv($fh, $row);
                }
            });
        } else {
            Stock::orderBy('id', 'desc')->chunk(500, function ($stock) use ($fields, $fh) {
                foreach ($stock as $item) {
                    $oemPart = '';
                    if (!is_null($item->oem_parts)) {
                        foreach (json_decode($item->oem_parts) as $oem_part) {

                            $oemPart .= $oem_part->name . ',';
                        }
                    }


                    $item->oem_parts = $oemPart;

                    $item->imei = $item->imei !== "" ? $item->imei : $item->serial;
                    $row = array_map(function ($field) use ($item) {
                        return $item->$field;
                    }, $fields);
                    fputcsv($fh, $row);
                }
            });
        }

        fclose($fh);
        convert_file_encoding($csvPath);
        header('Content-length: ' . filesize($csvPath));
        header('Content-Disposition: attachment; filename="Stock export.csv"');
        header('Content-type: application/vnd.ms-excel');
        readfile($csvPath);
        die;
    }

    /**
     * @return void
     */
    public function getExportAgedStock()
    {
        $stock = Stock::whereIn('status', [Stock::STATUS_IN_STOCK, Stock::STATUS_INBOUND, Stock::STATUS_BATCH])->where("created_at", "<", \Carbon\Carbon::now()->subDays(14)->startOfDay());

        $fields = [
            'Ref' => 'our_ref',
            'Sku' => 'sku',
            '3rd-party ref' => 'third_party_ref',
            'Name' => 'name',
            'Capacity' => 'capacity',
            'Colour' => 'colour',
            'Condition' => 'condition',
            'Grade' => 'grade',
            'Network' => 'network',
            'Status' => 'status',
            'VAT Type' => 'vat_type',
            'Sales price' => 'sale_price_formatted',
            'Purchase date' => 'purchase_date',
            'Purchase price' => 'purchase_price_formatted',
            'Location' => 'location',
            'Engineer Notes' => 'notes',
            'Non OEM Parts' => 'oem_parts',
            'Days Old' => 'days_old'
        ];


        $csvPath = tempnam('/tmp', 'stock-full-export-');
        unlink($csvPath);
        $csvPath .= '.csv';
        $fh = fopen($csvPath, 'w');
        fputcsv($fh, array_keys($fields));

        $stock->chunk(500, function ($items) use ($fields, $fh) {
            foreach ($items as $item) {

                $oemPart = '';
                if (!is_null($item->oem_parts)) {
                    foreach (json_decode($item->oem_parts) as $oem_part) {

                        $oemPart .= $oem_part->name . ',';
                    }
                }
                $item->oem_parts = $oemPart;

                $row = array_map(function ($field) use ($item) {
                    return $item->$field;
                }, $fields);
                fputcsv($fh, $row);
            }
        });


        fclose($fh);
        convert_file_encoding($csvPath);
        header('Content-length: ' . filesize($csvPath));
        header('Content-Disposition: attachment; filename="Aged_Stock_export.csv"');
        header('Content-type: application/vnd.ms-excel');
        readfile($csvPath);
        die;
    }


    public function getInStockExport()
    {

        ini_set('max_execution_time', 600);

        $fields = [
            'Ref' => 'our_ref',
            'Sku' => 'sku',
            '3rd-party ref' => 'third_party_ref',
            'IMEI / Serial ' => 'imei',
            'Country' => 'purchase_country',
            'Name' => 'name',
            'Capacity' => 'capacity',
            'Colour' => 'colour',
            'Condition' => 'condition',
            'Grade' => 'grade',
            'Network' => 'network',
            'Status' => 'status',
            'Cracked back' => 'cracked_back',
            'VAT Type' => 'vat_type',
            'Supplier' => 'supplier_name',
            'Purchase date' => 'purchase_date',
            'Product Purchase Price' => 'purchase_price_formatted',
            // 'Purchase price' => 'total_costs_formatted',
            'Purchase price' => 'total_cost_with_repair_formatted',
            'Purchase order Ref' => 'purchase_order_number',
            'Sold date' => 'sold_at',
            'Sales price' => 'sale_price_formatted',
            'GP' => 'gross_profit_formatted',
            'GP%' => 'gross_profit_percentage_formatted',
            'VAT Margin' => 'vat_formatted',
            'Total GP' => 'total_gross_profit_formatted',
            'Total GP%' => 'total_gross_profit_percentage_formatted',
            'Customer ID' => 'customer_id',
            'Customer Name' => 'customer_name',
            'Buyers Ref' => 'buyers_ref',
            'Location' => 'location',
            'No. Tests' => 'phone_check_updates',
            'Engineer Notes' => 'notes',
            'Non OEM Parts' => 'oem_parts',
            'Unlocked?' => 'unlocked_from_network',
            'Repair 1 Date' => 'repair_1_date',
            'Repair 1' => 'repair_1_parts',
            'Retest 1' => 'repair_1_retest',
            'Repair 2 Date' => 'repair_2_date',
            'Repair 2' => 'repair_2_parts',
            'Retest 2' => 'repair_2_retest',
            'Repair 3 Date' => 'repair_3_date',
            'Repair 3' => 'repair_3_parts',
            'Retest 3' => 'repair_3_retest',
        ];


        $csvPath = tempnam('/tmp', 'stock-full-export-');
        unlink($csvPath);
        $csvPath .= '.csv';
        $fh = fopen($csvPath, 'w');
        fputcsv($fh, array_keys($fields));


        Stock::whereIn('status', [
            Stock::STATUS_IN_STOCK,
            Stock::STATUS_READY_FOR_SALE,
            Stock::STATUS_RETAIL_STOCK,
            Stock::STATUS_LISTED_ON_AUCTION,
            Stock::STATUS_3RD_PARTY,
            Stock::STATUS_RESERVED_FOR_ORDER,
            Stock::STATUS_BATCH,
            Stock::STATUS_ALLOCATED,
        ])->orderBy('id', 'desc')->chunk(500, function ($stock) use ($fields, $fh) {
            foreach ($stock as $item) {
                $oemPart = '';
                if (!is_null($item->oem_parts)) {
                    foreach (json_decode($item->oem_parts) as $oem_part) {
                        $oemPart .= $oem_part->name . ',';
                    }
                }
                $item->oem_parts = $oemPart;
                $item->imei = $item->imei !== "" ? $item->imei : $item->serial;
                $row = array_map(function ($field) use ($item) {
                    return $item->$field;
                }, $fields);
                fputcsv($fh, $row);
            }
        });

        fclose($fh);
        convert_file_encoding($csvPath);
        header('Content-length: ' . filesize($csvPath));
        header('Content-Disposition: attachment; filename="Stock export.csv"');
        header('Content-type: application/vnd.ms-excel');
        readfile($csvPath);
        die;

    }


    /**
     * @param Request $request
     * @return void
     */
    public function getExportByFilter(Request $request)
    {

        ini_set('max_execution_time', 1000);

        $stock = Stock::orderBy('id', 'desc');

        if ($request->purchase_end_date && $request->purchase_start_date) {
            $stock->whereBetween('purchase_date', [$request->purchase_start_date, $request->purchase_end_date]);
        }
        if ($request->sale_end_date && $request->sale_start_date) {
            $stock->whereBetween('sold_at', [$request->sale_start_date, $request->sale_end_date]);
        }
        if ($request->make) {
            $stock->where('make', $request->make);
        }
        if ($request->model) {
            $stock->whereHas('product', function ($q) use ($request) {
                $q->where('model', $request->model);
            });
        }
        if ($request->grade) {
            $stock->where('grade', $request->grade);
        }
        if ($request->vat_type) {
            $stock->where('vat_type', $request->vat_type);
        }
        if ($request->customer_id) {
            $stock->whereHas('sale.user', function ($q) use ($request) {
                $q->where('invoice_api_id', $request->customer_id);
            });
        }
        if ($request->customer_name) {
            $stock->whereHas('sale.user', function ($q) use ($request) {
                $q->where('id', $request->customer_name);
            });
        }
        if ($request->supplier) {
            $stock->whereHas('supplier', function ($q) use ($request) {
                $q->where('name', $request->supplier);
            });
        }
        if ($request->product_type) {
            $stock->where('product_type', $request->product_type);
        }


        $fields = [
            'Ref' => 'our_ref',
            'Sku' => 'sku',
            '3rd-party ref' => 'third_party_ref',
            'IMEI / Serial' => 'imei',
            'Make' => 'make',
            'No Touch / Face ID' => 'touch_id_working',
            'Cracked back' => 'cracked_back',
            'VAT Type' => 'vat_type',
            'Country' => 'purchase_country',
            'Name' => 'name',
            'Model' => 'model',
            'Capacity' => 'capacity',
            'Colour' => 'colour',
            'Condition' => 'condition',
            'Grade' => 'grade',
            'Network' => 'network',
            'Status' => 'status',
            'Supplier' => 'supplier_name',
            'Purchase date' => 'purchase_date',
            'Product Purchase Price' => 'purchase_price_formatted',
            //  'Purchase price' => 'total_costs_formatted',
            'Purchase price' => 'total_cost_with_repair_formatted',
            'Purchase order Ref' => 'purchase_order_number',
            'Sold date' => 'sold_at',
            'Sales price' => 'sale_price_formatted',
            'GP' => 'gross_profit_formatted',
            'GP%' => 'gross_profit_percentage_formatted',
            'VAT Margin' => 'vat_formatted',
            'Total GP' => 'total_gross_profit_formatted',
            'Total GP%' => 'total_gross_profit_percentage_formatted',
            'Customer ID' => 'customer_id',
            'Customer Name' => 'customer_name',
            'Buyers Ref' => 'buyers_ref',
            'Location' => 'location',
            'No. Tests' => 'phone_check_updates',
            'Engineer Notes' => 'notes',
            'Supplier Name' => 'supplier_name',
            'Product Type' => 'product_type',
            'Non OEM Parts' => 'oem_parts',
            'Unlocked?' => 'unlocked_from_network',
            'Repair 1 Date' => 'repair_1_date',
            'Repair 1' => 'repair_1_parts',
            'Retest 1' => 'repair_1_retest',
            'Repair 2 Date' => 'repair_2_date',
            'Repair 2' => 'repair_2_parts',
            'Retest 2' => 'repair_2_retest',
            'Repair 3 Date' => 'repair_3_date',
            'Repair 3' => 'repair_3_parts',
            'Retest 3' => 'repair_3_retest',
        ];


        $csvPath = tempnam('/tmp', 'stock-full-export-');
        unlink($csvPath);
        $csvPath .= '.csv';
        $fh = fopen($csvPath, 'w');
        fputcsv($fh, array_keys($fields));

        $stock->chunk(500, function ($items) use ($fields, $fh) {
            foreach ($items as $item) {

                //dd(json_decode($item->oem_parts));
                $oemPart = '';
                if (!is_null($item->oem_parts)) {
                    foreach (json_decode($item->oem_parts) as $oem_part) {

                        $oemPart .= $oem_part->name . ',';
                    }
                }


                $item->created_at = Carbon::createFromFormat('Y-m-d H:i:s', $item->created_at)->format('Y-m-d');
                $item->imei = $item->imei !== "" ? $item->imei : $item->serial;
                $item->oem_parts = $oemPart;


                $row = array_map(function ($field) use ($item) {
                    return $item->$field;
                }, $fields);
                fputcsv($fh, $row);
            }
        });


        fclose($fh);
        convert_file_encoding($csvPath);
        header('Content-length: ' . filesize($csvPath));
        header('Content-Disposition: attachment; filename="Stock_export.csv"');
        header('Content-type: application/vnd.ms-excel');
        readfile($csvPath);
        die;


    }


    /**
     * @param NonSerialisedStockRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postAddNonSerialisedStock(NonSerialisedStockRequest $request)
    {


        $vatType = $request->vat_type;


        $product = Product::find($request->product_id);

        if ($request->code != "784199") {
            return back()->with('messages.error', 'Invalid Authorisation Code');
        }


        $calculations = calculationOfProfit($request->sale_price, $product->purchase_price, $vatType, $product->purchase_price);

        $stock = new Stock();
        $stock->grade = $request->grade;
        $stock->sale_price = $request->sale_price;
        $stock->vat_type = $request->vat_type;
        $stock->product_id = $request->product_id;
        $stock->non_serialised = 1;
        $stock->capacity = !is_null($product) ? $product->capacity : '';
        $stock->colour = '';
        $stock->serial='';
        $stock->imei='';
        $stock->name='';
        $stock->product_type='';
        $stock->network='';
        $stock->purchase_order_number='';
        $stock->ps_model=0;

        $stock->purchase_price = !is_null($product) ? $product->purchase_price : '';


        $stock->profit = $calculations['profit'];
        $stock->marg_vat = $calculations['marg_vat'];
        $stock->true_profit = $calculations['true_profit'];
        $stock->sale_vat = $calculations['sale_vat'];
        $stock->total_price_ex_vat = $calculations['total_price_ex_vat'];
        $stock->save();

        return redirect()->route('stock.single', ['id' => $stock->id]);


    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function getDelete()
    {
        return view('stock.delete');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postDeletePermanently(Request $request)
    {
        $item = Stock::findOrFail($request->id);

        if ($item->status != Stock::STATUS_DELETED) {
            return back()->with('messages.error', 'Invalid Item Status');
        }

        $item->delete();

        return redirect()->route('stock')->with('messages.success', 'Deleted');
    }


    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postDelete(Request $request)
    {
        if (!trim($request->imeis)) {
            return back()->with('messages.error', 'At least one IMEI/Ref/Serial is required');
        }

        $imeis = preg_split('/[\s,]+/', $request->imeis);
        if (count($imeis) == 1 && $imeis[0] == "") {
            return back()->with('messages.error', 'At least one IMEI/Ref/Serial is required');
        }

        if ($request->code != "784199") {
            return back()->with('messages.error', "Invalid Authorisation Code");
        }

        $message = "";
        foreach ($imeis as $key => $imei) {
            $lockKey = substr('delete_' . md5(rand()), 0, 32);
            Stock::multiRef($imei)->where('locked_by', '')->update(['locked_by' => $lockKey]);
            $item = Stock::multiRef($imei)->first();

            if (!$item) {
                $message .= "#$key $imei - Stock item not found.\n";
                continue;
            }

            if (
                !in_array($item->status, [Stock::STATUS_IN_STOCK, Stock::STATUS_INBOUND]) ||
                $item->locked_by !== $lockKey
            ) {
                Stock::multiRef($imei)->where('locked_by', $lockKey)->update(['locked_by' => '']);
                $message .= "#$key $imei Stock item can only be deleted if its status is Inbound or In Stock.\n";
                continue;
            } else {
                SysLog::log('Cancelling deletion, restoring empty lock key from ' . $lockKey, Auth::user()->id, $item->id);
            }

            SysLog::log('Deleting item', Auth::user()->id, $item->id);
            $item->status = Stock::STATUS_DELETED;
            $item->save();

            StockLog::create([
                'user_id' => Auth::user()->id,
                'stock_id' => $item->id,
                'content' => "This item was deleted by " . Auth::user()->id
            ]);

            $message .= "#$key $imei - Deleted\n";
        }

        return back()->with('messages.info-custom', $message)->withInput($request->all());
    }



}
