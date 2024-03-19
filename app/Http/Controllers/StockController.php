<?php

namespace App\Http\Controllers;


use App\Exports\InventoryExport;
use App\Exports\ReadyForSaleExport;
use App\Exports\RetailStockExport;
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
use App\Models\NewInventory;
use App\Models\PhoneCheck;
use App\Models\User;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Request as RequestFacade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\StockLog;
use App\Models\ImageProcessing;
use App\Models\SysLog;
use App\Models\Supplier;
use Illuminate\Support\Facades\View;



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
//                $client = new Client();
//                $response = $client->get("https://alpha.imeicheck.com/api/php-api/create?key=". config('services.imei_check_api_key'). "&service=".config('services.imei_check_service_code')."&imei=".$checkIMEI);



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


                $stockNetworkUpdate=Stock::find($item->id);


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




                StockLog::create([
                    'user_id' => Auth::user()->id,
                    'stock_id' => $item->id,
                    'content' => "Update Network:-". $stockNetworkUpdate->network,
                ]);
            }

            StockLog::create([
                'user_id' => Auth::user()->id,
                'stock_id' => $item->id,
                'content' => "Imported. Purchase Price: " . money_format($item->purchase_price),
               // 'content' => "Imported. Purchase Price: " . $item->purchase_price,
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

        $content = $newDate . "Internal Repair (" . $repair->Repairengineer->name . " ):" . $repairItem->parts . " - " . money_format($stock->part_cost);


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


    /**
     * @param Request $request
     * @return string|void
     */
    public function getStockInformation(Request $request)
    {

        $id = $request->id;


        if (strpos($request->value, "RCT") !== false) {
            $value = preg_replace('/[^0-9.]+/', '', $request->value);

        } else {
            $value = $request->value;
        }

        if ($request->value) {
            $stock = Stock::whereNotIn('status', [Stock::STATUS_REPAIR, Stock::STATUS_SOLD, Stock::STATUS_PAID, Stock::STATUS_ALLOCATED])
                ->where(function ($query) use ($value) {

                    $query->where('id', 'like', '%' . $value . '%')->orWhere('imei', 'like', '%' . $value . '%')
                        ->orWhere('serial', 'like', '%' . $value . '%')
                        ->orWhere('name', 'like', '%' . $value . '%')
                        ->orWhere('sku', 'like', '%' . $value . '%');

                })->get();
        }


        $output = '<ul class="dropdown-menu" style="display:block;width: 400px !important;
    overflow: scroll;
    height: 165px;">';

        if (!count($stock)) {

            $output .= '

       <li id=' . $id . '><a href="#">No Data Found</a></li>
       ';
            return $output;

        }

        foreach ($stock as $data) {


            if ($data->imei !== '') {
                $imeiNumber = $data->imei;
            } else if ($data->serial !== '') {
                $imeiNumber = $data->serial;
            } else if ($data->sku !== '') {
                $imeiNumber = $data->sku;
            } else {
                $imeiNumber = "RCT" . $data->id;
            }

            $output .= '

       <li id=' . $id . '><a href="#"> ' . $data->long_name . ':' . "$imeiNumber" . '</a></li>
       ';
        }
        $output .= '</ul>';
        echo $output;

    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postItemDelete(Request $request)
    {
        $item = Stock::findOrFail($request->stock_id);
        $ref = $item->our_ref;
        if ($item->status == Stock::STATUS_INBOUND && (!$item->locked_by || $item->locked_by == '')) {
            $item->delete();
            return redirect()->route('stock')->with('messages.success', "Item $ref has been removed.");
        }

        return back()->with('messages.error', "$ref - Unable to delete - wrong status or item locked");
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\JsonResponse
     */
    public function getOverview(Request $request)
    {
        // Pagination handled manually because Laravel can't handle it with queries using 'group by'.


        $unmpping = Stock::whereNull('product_id')
            // ->join('products','products.id','=','new_stock.product_id')
            ->whereIn('status', [
                'In Stock',
                'Re-test',
                'Batch',
                'In Repair',
                '3rd Party',
                'Ready for Sale',
                'Retail Stock',
                'Listed on Auction',
                'Reserved for Order',
                'Allocated'])
            ->count();
        $unmappingWithZero = Stock::where('product_id', '=', 0)
            ->whereIn('status', ['In Stock',
                'Re-test',
                'Batch',
                'In Repair',
                '3rd Party',
                'Ready for Sale',
                'Retail Stock',
                'Listed on Auction',
                'Reserved for Order',
                'Allocated'])->count();


        $inBoundUnmpping = Stock::whereNull('product_id')
            //   ->join('products','products.id','=','new_stock.product_id')
            ->whereIn('status', ['Inbound'])
            ->count();

        $inBoundUnmappingWithZero = Stock::fromRequest($request)->where('product_id', '=', 0)
            ->whereIn('status', ['Inbound'])->count();

        $stock = NewInventory::fromRequest($request)->orderBy('qty_in_stock', 'desc')->paginate(config('app.pagination'))->appends($request->all());

        $totalQty = NewInventory::fromRequest($request)->sum('qty_in_stock');
        $totalQtyInStock = NewInventory::fromRequest($request)->sum('qty_in_stock');
        $totalInbound = NewInventory::fromRequest($request)->sum('qty_in_bound');
        $totalQtyPurchasePrice = NewInventory::fromRequest($request)->sum('total_purchase_price');
        $totalQtyTested = NewInventory::fromRequest($request)->sum('qty_in_tested');
        $totalOfInBound = NewInventory::fromRequest($request)->sum('qty_in_bound');
        $totalQtyGradeA = NewInventory::fromRequest($request)->sum('grade_a');
        $totalQtyGradeB = NewInventory::fromRequest($request)->sum('grade_b');
        $totalQtyGradeC = NewInventory::fromRequest($request)->sum('grade_c');
        $totalQtyGradeD = NewInventory::fromRequest($request)->sum('grade_d');
        $totalQtyGradeE = NewInventory::fromRequest($request)->sum('grade_e');
        $totalQtyCrackBack = NewInventory::fromRequest($request)->sum('cracked_back');
        $totalQtyTotalTouchId = NewInventory::fromRequest($request)->sum('no_touch_face_id');
        $totalQtyTotalLocked = NewInventory::fromRequest($request)->sum('network_locked');


        if ($request->ajax()) {
            return response()->json([
                'itemsHtml' => view('stock.overview-items', compact('stock', 'totalQtyInStock', 'totalQtyGradeA', 'totalQtyGradeB', 'totalQtyTotalLocked', 'totalQtyTotalTouchId', 'totalQtyGradeC', 'totalOfInBound', 'totalQtyCrackBack', 'totalQtyGradeD', 'totalQtyGradeE', 'totalQtyTested', 'totalQtyPurchasePrice', 'totalQty', 'totalInbound', 'unmpping', 'unmappingWithZero', 'inBoundUnmpping', 'inBoundUnmappingWithZero'))->render(),
                'paginationHtml' => '' . $stock->render(),
            ]);
        } else {
            return view('stock.overview', compact('stock', 'totalQtyGradeA', 'totalQtyInStock', 'totalQtyGradeB', 'totalQtyTotalLocked', 'totalQtyTotalTouchId', 'totalQtyGradeC', 'totalOfInBound', 'totalQtyCrackBack', 'totalQtyGradeD', 'totalQtyGradeE', 'totalQtyTested', 'totalQtyPurchasePrice', 'totalQty', 'totalInbound', 'unmpping', 'unmappingWithZero', 'inBoundUnmpping', 'inBoundUnmappingWithZero'));
        }


    }


    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function inventoryExportCsv()
    {

        return Excel::download(new InventoryExport(), 'inventoryExport.csv');


    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function getPurchaseOrderStats(Request $request)
    {

        $stats = [];
        $items_sold = 0;
        $items_to_sell = 0;
        $items_in_repair = 0;
        $total = 0;
        $estProfitPre = 0;
        $estProfit = 0;
        $totalFess = 0;
        $psModel = 0;
        $items_total=0;
        $estProfitNonSPModel = 0;
        if ($request->purchase_order_number) {
            if ($request->purchase_order_number == "Zapper")
                $query = \App\Stock::where('vendor_name', "Zapper");
            else
                $query = Stock::where('purchase_order_number', $request->purchase_order_number);
            $count = with(clone $query)->count();
            if ($count) {

                foreach ($query->get() as $stock) {
                    $total += $stock->total_cost_with_repair;
                    $psModel = $stock->ps_model;
                }

                $stats['total'] = $count;
                $stats['total_tested'] = with(clone $query)->whereNotIn('status', [Stock::STATUS_RETURNED_TO_SUPPLIER])->count();

                $items_to_sell = with(clone $query)->whereIn('status', [Stock::STATUS_IN_STOCK, Stock::STATUS_BATCH, Stock::STATUS_RETAIL_STOCK, Stock::STATUS_ALLOCATED])->paginate(25);
                $items_sold = with(clone $query)->whereIn('status', [Stock::STATUS_PAID, Stock::STATUS_SOLD])->paginate(25);
                $items_total = with(clone $query)->whereIn('status', [Stock::STATUS_PAID, Stock::STATUS_SOLD])->count();
                $items_in_repair = with(clone $query)->whereIn('status', [Stock::STATUS_REPAIR])->paginate(25);
                $items_returned = with(clone $query)->whereIn('status', [Stock::STATUS_RETURNED_TO_SUPPLIER])->paginate(25);

                $tested = with(clone $query)->whereNotIn('status', [Stock::STATUS_RETURNED_TO_SUPPLIER])->where(function ($w) {
                    $w->whereHas('phone_check', function ($q) {
                        $q->where('status', PhoneCheck::STATUS_DONE);
                    });
                    $w->orWhere('notes', '!=', '');
                })->count();


                foreach ($query->get() as $stock) {


                    if (count($stock->sale) > 0) {

                        $trueProfile = 0;
                        $totalCost = 0;
                        $totalMarg_vat = 0;
                        foreach ($stock->sale->stock as $saleStock) {
                            $totalCost += $saleStock->total_cost_with_repair;
                            $totalMarg_vat += $saleStock->marg_vat;
                        }

                        if ($stock->vat_type === Stock::VAT_TYPE_STD) {
                            $trueProfile = ($stock->sale->invoice_total_amount / 1.2) - $totalCost;

                        } else {
                            $trueProfile = $stock->sale->invoice_total_amount - $totalMarg_vat - $totalCost;
                        }


                        $estProfitNonSPModel = $trueProfile - $stock->sale->platform_fee;

                        $shippingCostAvg = ($stock->sale->platform_fee + $stock->sale->shipping_cost) / count($stock->sale->stock);
                        $delivery_charges = 0;
                        $finalShippingCost = 0;

                        if (!is_null($stock->sale->delivery_charges)) {
                            $delivery_charges = ($stock->sale->delivery_charges / 1.2) / count($stock->sale->stock);

                        }


                        $finalShippingCost = $shippingCostAvg - $delivery_charges;

                        $estProfit += $stock->true_profit - $finalShippingCost;


                        $totalFess += $stock->sale->platform_fee + $stock->sale->shipping_cost;
                    }
                }


                $stats['total_price_ex_vat'] = with(clone $query)->sum('total_price_ex_vat');
                $stats['total_vat'] = with(clone $query)->sum('marg_vat');
                $stats['total_true_profit'] = with(clone $query)->sum('true_profit');
                $stats['total_profit'] = with(clone $query)->sum('profit');
                $stats['est_profit'] = $estProfit;


                $stats['purchase_price'] = $total;
                $stats['sales_price'] = with(clone $query)->sum('sale_price');
                $stats['unlock_cost'] = with(clone $query)->sum('unlock_cost');
                $stats['part_cost'] = with(clone $query)->sum('part_cost');
                $stats['gross_profit'] = $stats['sales_price'] - $stats['purchase_price'];
                $stats['profit_ratio'] = ($stats['purchase_price'] + $stats['unlock_cost'] + $stats['part_cost']) / $stats['sales_price'] * 100;
                $stats['total_fees'] = $totalFess;
                $stats['ex_sales_price'] = with(clone $query)->sum('total_price_ex_vat');
                if ($query->get()[0]['vat_type'] === Stock::VAT_TYPE_STD) {
                    $estProfitPre = $estProfit !== '0' ? ($estProfit / $stats['ex_sales_price']) * 100 : 0;
                } else {
                    $estProfitPre = $estProfit !== '0' ? ($estProfit / $stats['sales_price']) * 100 : 0;
                }


                $stats['est_profit_pre'] = $estProfitPre;

                $stats['fully_working_no_touch_id'] = with(clone $query)->where('grade', Stock::GRADE_FULLY_WORKING_NO_TOUCH_ID)->count();
                $stats['fully_working'] = with(clone $query)->where('grade', Stock::GRADE_FULLY_WORKING)->count();
                $stats['minor_fault'] = with(clone $query)->where('grade', Stock::GRADE_MINOR_FAULT)->count();
                $stats['major_fault'] = with(clone $query)->where('grade', Stock::GRADE_MAJOR_FAULT)->count();
                $stats['broken'] = with(clone $query)->where('grade', Stock::GRADE_BROKEN)->count();

                $stats['condition_a'] = with(clone $query)->where('condition', Stock::CONDITION_A)->count();
                $stats['condition_b'] = with(clone $query)->where('condition', Stock::CONDITION_B)->count();
                $stats['condition_c'] = with(clone $query)->where('condition', Stock::CONDITION_C)->count();
                $stats['condition_d'] = with(clone $query)->where('condition', Stock::CONDITION_D)->count();
                $stats['condition_e'] = with(clone $query)->where('condition', Stock::CONDITION_E)->count();


                $stats['fully_working_A'] = with(clone $query)->where('condition', Stock::CONDITION_A)->where('grade', Stock::GRADE_FULLY_WORKING)->count();
                $stats['fully_working_B'] = with(clone $query)->where('condition', Stock::CONDITION_B)->where('grade', Stock::GRADE_FULLY_WORKING)->count();
                $stats['fully_working_C'] = with(clone $query)->where('condition', Stock::CONDITION_C)->where('grade', Stock::GRADE_FULLY_WORKING)->count();
                $stats['fully_working_D'] = with(clone $query)->where('condition', Stock::CONDITION_D)->where('grade', Stock::GRADE_FULLY_WORKING)->count();
                $stats['fully_working_E'] = with(clone $query)->where('condition', Stock::CONDITION_E)->where('grade', Stock::GRADE_FULLY_WORKING)->count();

                $stats['minor_fault_A'] = with(clone $query)->where('condition', Stock::CONDITION_A)->where('grade', Stock::GRADE_MINOR_FAULT)->count();
                $stats['minor_fault_B'] = with(clone $query)->where('condition', Stock::CONDITION_B)->where('grade', Stock::GRADE_MINOR_FAULT)->count();
                $stats['minor_fault_C'] = with(clone $query)->where('condition', Stock::CONDITION_C)->where('grade', Stock::GRADE_MINOR_FAULT)->count();
                $stats['minor_fault_D'] = with(clone $query)->where('condition', Stock::CONDITION_D)->where('grade', Stock::GRADE_MINOR_FAULT)->count();
                $stats['minor_fault_E'] = with(clone $query)->where('condition', Stock::CONDITION_E)->where('grade', Stock::GRADE_MINOR_FAULT)->count();

                $stats['major_fault_A'] = with(clone $query)->where('condition', Stock::CONDITION_A)->where('grade', Stock::GRADE_MAJOR_FAULT)->count();
                $stats['major_fault_B'] = with(clone $query)->where('condition', Stock::CONDITION_B)->where('grade', Stock::GRADE_MAJOR_FAULT)->count();
                $stats['major_fault_C'] = with(clone $query)->where('condition', Stock::CONDITION_C)->where('grade', Stock::GRADE_MAJOR_FAULT)->count();
                $stats['major_fault_D'] = with(clone $query)->where('condition', Stock::CONDITION_D)->where('grade', Stock::GRADE_MAJOR_FAULT)->count();
                $stats['major_fault_E'] = with(clone $query)->where('condition', Stock::CONDITION_E)->where('grade', Stock::GRADE_MAJOR_FAULT)->count();


                $stats['networks']['unlocked'] = with(clone $query)->where('network', 'Unlocked')->count();
                $stats['networks']['unknown'] = with(clone $query)->where('network', 'Unknown')->count();
                $stats['networks']['vodafone'] = with(clone $query)->where('network', 'Vodafone')->count();
                $stats['networks']['ee'] = with(clone $query)->where('network', 'EE')->count();
                $stats['networks']['three'] = with(clone $query)->where('network', 'Three')->count();
                $stats['networks']['o2'] = with(clone $query)->where('network', 'O2')->count();
                $stats['vat_type'] = $query->get()->toArray()[0]['vat_type'];

                $other = $stats['total'];
                foreach ($stats['networks'] as $count) $other -= $count;
                $stats['networks']['other'] = $other;
            }
        }


        return view('stock.purchase-order-stats',
            [
                'psModel'=>$psModel,
                'stats'=>$stats,
                'items_to_sell'=>$items_to_sell,
                'items_in_repair'=>$items_in_repair,
                'items_total'=>$items_total
            ]);
       // return view('stock.purchase-order-stats', compact('psModel', 'stats', 'items_sold', 'items_to_sell', 'items_in_repair', 'items_returned', 'items_total'));
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postPurchaseOrderUpdatePSModel(Request $request)
    {

        if (!$request->purchase_order_number) {
            return back()->with('messages.error', 'PO Number is required');
        }

        $updated = Stock::where('purchase_order_number', $request->purchase_order_number)->update(['ps_model' => $request->ps_model]);

        return back()->with('messages.success', "$updated Item(s) were updated");
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\JsonResponse
     */
    public function getPurchaseOrdersAll(Request $request)
    {
        ini_set('max_execution_time', 120);
        ini_set('memory_limit', '4G');
        $query = Stock::where('vendor_name', "Zapper");
        $orders = Stock::where('purchase_order_number', '<>', '')->orderBy('purchase_date', 'desc')->groupBy('purchase_order_number');
        $reitem_unsold = null;
        if ($request->supplier_id) {
            $orders->where('supplier_id', $request->supplier_id);

        }
        if ($request->supplier_start && $request->supplier_end) {

            $orders->whereBetween('purchase_date', [$request->supplier_start . ' 00:00:00', $request->supplier_end . ' 23:59:59']);
            // $orders->where('supplier_id', $request->supplier_id);
        }
        if ($request->vat_type) {

            $orders->where('vat_type', $request->vat_type);
        }
        if ($request->purchase_order_number) {
            $orders->where('purchase_order_number', $request->purchase_order_number);

        }
        if ($request->items_unsold) {
            if ($request->items_unsold === "Yes") {
                $orders->whereIn('status', [Stock::STATUS_IN_STOCK,
                    Stock::STATUS_BATCH,
                    Stock::STATUS_RETAIL_STOCK,
                    Stock::STATUS_READY_FOR_SALE,
                    Stock::STATUS_RE_TEST,
                    Stock::STATUS_ALLOCATED,
                    Stock::STATUS_INBOUND,
                    Stock::STATUS_LISTED_ON_AUCTION,
                    Stock::STATUS_RESERVED_FOR_ORDER,
                    Stock::STATUS_REPAIR]);
            } else {

                $reitem_unsold = $request->items_unsold;

            }


        }
        $orders = $orders->paginate(15);
        foreach ($orders as $order) {

            $estProfit = 0;
            $estProfitPre = 0;
            $totalOfShipping = 0;
            $query = Stock::where('purchase_order_number', $order->purchase_order_number);
            //  $querySold = Stock::where('purchase_order_number', $order->purchase_order_number)->whereIn('status', [Stock::STATUS_PAID, Stock::STATUS_SOLD])->get();

            $b2bQty = 0;
            $retailQty = 0;
            foreach (with(clone $query)->with('sale_history')->whereIn('status', [Stock::STATUS_PAID, Stock::STATUS_SOLD])->get() as $sale_his) {

                if (count($sale_his->sale_history) > 0) {
                    if ($sale_his->purchase_order_number === "29119") {
                        $userId = $sale_his->sale_history[1]->user_id;
                    } else {
                        $userId = $sale_his->sale_history[0]->user_id;
                    }
                    $user = User::find($userId);

                    if (isset($user->quickbooks_customer_category)) {
                        if (in_array($user->quickbooks_customer_category, ['B2B Device Sales'])) {
                            $b2bQty++;
                        } else if (in_array($user->quickbooks_customer_category, ['Consumer Device Sales', 'eBay Sales', 'Backmarket Sales', 'Misc Sales', 'Rest of World Device Sales'])) {
                            $retailQty++;
                        }
                    }
                }
            }

            $exRepairCost = 0;
            foreach (with(clone $query)->with('repair_item')->get() as $repair) {
                if (count($repair->repair_item)) {
                    foreach ($repair->repair_item as $items) {
                        if ($items->estimate_repair_cost > 0) {

                            $exRepairCost += $items->estimate_repair_cost;

                        }

                    }
                }
            }

            $order->items = with(clone $query)->count();
            $order->total_purchase_stock_price = with(clone $query)->whereIn('status', [Stock::STATUS_IN_STOCK])->sum('purchase_price');

            if ($order->vat_type === "Margin") {
                $order->sold_sales_price = with(clone $query)->whereIn('status', [Stock::STATUS_PAID, Stock::STATUS_SOLD])->sum('sale_price');
                $order->total_sales_stock_price = with(clone $query)->whereIn('status', [Stock::STATUS_IN_STOCK, Stock::STATUS_BATCH, Stock::STATUS_RETAIL_STOCK, Stock::STATUS_READY_FOR_SALE, Stock::STATUS_RE_TEST, Stock::STATUS_ALLOCATED, Stock::STATUS_INBOUND, Stock::STATUS_LISTED_ON_AUCTION, Stock::STATUS_RESERVED_FOR_ORDER, Stock::STATUS_REPAIR])->sum('sale_price');
                //  $order->total_sales_price=with(clone $query)->whereIn('status', [Stock::STATUS_PAID,Stock::STATUS_SOLD])->sum('sale_price');
            } else {
                $order->sold_sales_price = with(clone $query)->whereIn('status', [Stock::STATUS_PAID, Stock::STATUS_SOLD])->sum('total_price_ex_vat');
                $order->total_sales_stock_price = with(clone $query)->whereIn('status', [Stock::STATUS_IN_STOCK, Stock::STATUS_BATCH, Stock::STATUS_RETAIL_STOCK, Stock::STATUS_READY_FOR_SALE, Stock::STATUS_RE_TEST, Stock::STATUS_ALLOCATED, Stock::STATUS_INBOUND, Stock::STATUS_LISTED_ON_AUCTION, Stock::STATUS_RESERVED_FOR_ORDER, Stock::STATUS_REPAIR])->sum('total_price_ex_vat');
                //$order->total_sales_price=with(clone $query)->whereIn('status', [Stock::STATUS_PAID,Stock::STATUS_SOLD])->sum('total_price_ex_vat');
            }

            $order->total_sales_price = with(clone $query)->whereIn('status', [Stock::STATUS_PAID, Stock::STATUS_SOLD])->sum('sale_price');
            foreach (with(clone $query)->whereIn('status', [Stock::STATUS_PAID, Stock::STATUS_SOLD])->get() as $soldItem) {

                if (!is_null($soldItem->sale)) {

                    if($soldItem->sale->platform===Stock::PLATFROM_RECOMM){
                        $totalOfShipping += $soldItem->sale->platform_fee;
                    }else{
                        $totalOfShipping += $soldItem->sale->platform_fee + $soldItem->sale->shipping_cost;
                    }

                    $shippingCostAvg = ($soldItem->sale->platform_fee + $soldItem->sale->shipping_cost) / count($soldItem->sale->stock);

                    $delivery_charges = 0;
//                    $finalShippingCost=0;

                    if (!is_null($soldItem->sale->delivery_charges)) {
                        $delivery_charges = ($soldItem->sale->delivery_charges / 1.2) / count($soldItem->sale->stock);
                    }

                    //   dd($order);
                    $finalShippingCost = $shippingCostAvg - $delivery_charges;

                    if ($soldItem->vat_type === Stock::VAT_TYPE_STD) {
                        $trueProfile = ($soldItem->sale->invoice_total_amount / 1.2) - $soldItem->total_cost_with_repair;

                    } else {
                        $trueProfile = $soldItem->sale->invoice_total_amount - $soldItem->marg_vat - $soldItem->total_cost_with_repair;
                    }
//
//                    $estProfitNonSPModel=$trueProfile - $soldItem->sale->platform_fee;
//
//
//
//
//                    if(abs($estProfitNonSPModel) >0){
//                        $estProfit += $estProfitNonSPModel- $soldItem->sale->shipping_cost;
//
//                    }else{
//                        $estProfit += $soldItem->true_profit - $soldItem->sale->platform_fee - $soldItem->sale->shipping_cost;
//                    }


                    $estProfit += $soldItem->true_profit - $finalShippingCost;


//                    if($order->purchase_order_number==="311023"){
//                      dd($estProfit);
//                    }


                    //  $estProfit += $soldItem->true_profit - $soldItem->sale->platform_fee;
                }

            }

            $order->est_profitPre = $order->total_sales_price > 0 ? $estProfit / $order->total_sales_price : '';
//            $order->ex_sales_price= with(clone $query)->sum('total_price_ex_vat');


            $order->total_purchase_price_cost = with(clone $query)->sum('purchase_price') + with(clone $query)->sum('part_cost') + with(clone $query)->sum('unlock_cost') + $exRepairCost;
            $order->total_purchase_price = with(clone $query)->sum('purchase_price');
            $order->total_part_cost = with(clone $query)->sum('part_cost');
            $order->total_unlock_cost = with(clone $query)->sum('unlock_cost');
            $order->total_unlock_qty = with(clone $query)->where('unlock_cost', '>', 0)->count();
            $order->est_profit = $estProfit;
            $order->final_cost = $totalOfShipping;


            $order->items_sold = with(clone $query)->whereIn('status', [Stock::STATUS_SOLD, Stock::STATUS_PAID])->count();
            $order->items_to_sell = with(clone $query)->whereIn('status', [Stock::STATUS_IN_STOCK, Stock::STATUS_BATCH, Stock::STATUS_RETAIL_STOCK, Stock::STATUS_READY_FOR_SALE, Stock::STATUS_RE_TEST, Stock::STATUS_ALLOCATED, Stock::STATUS_INBOUND, Stock::STATUS_LISTED_ON_AUCTION, Stock::STATUS_RESERVED_FOR_ORDER, Stock::STATUS_REPAIR])->count();
            //  $order->items_in_repair = with(clone $query)->count();
            $order->items_returned = with(clone $query)->whereIn('status', [Stock::STATUS_RETURNED_TO_SUPPLIER])->count();

            // $order->total_items_returned_value = with(clone $query)->whereIn('status', [Stock::STATUS_RETURNED_TO_SUPPLIER])->sum('purchase_price') + with(clone $query)->whereIn('status', [Stock::STATUS_RETURNED_TO_SUPPLIER])->sum('part_cost') + with(clone $query)->whereIn('status', [Stock::STATUS_RETURNED_TO_SUPPLIER])->sum('unlock_cost');
            if ($order->vat_type === "Margin") {
                $order->total_items_returned_value = with(clone $query)->whereIn('status', [Stock::STATUS_RETURNED_TO_SUPPLIER])->sum('sale_price');
            } else {
                $order->total_items_returned_value = with(clone $query)->whereIn('status', [Stock::STATUS_RETURNED_TO_SUPPLIER])->sum('total_price_ex_vat');
            }

            $order->total_return_supplier_purchas = with(clone $query)->whereIn('status', [Stock::STATUS_RETURNED_TO_SUPPLIER])->sum('purchase_price');

            $order->items_in_repair = with(clone $query)->whereIn('status', [Stock::STATUS_REPAIR])->count();

            $order->items_in_retest = with(clone $query)->whereIn('status', [Stock::STATUS_RE_TEST])->count();
            $order->items_listed_on_auction = with(clone $query)->whereIn('status', [Stock::STATUS_LISTED_ON_AUCTION])->count();
            $order->items_reserved_for_order = with(clone $query)->whereIn('status', [Stock::STATUS_RESERVED_FOR_ORDER])->count();
            $order->items_3rd_party = with(clone $query)->whereIn('status', [Stock::STATUS_3RD_PARTY])->count();
            $order->supplier = !is_null($order->supplier_id) ? $order->supplier->name : '-';
            $order->net_purchase_price = $order->total_purchase_price_cost - $order->total_return_supplier_purchas;
            $order->profit = $order->total_sales_price - $order->total_purchase_price;


            $order->total_price_ex_vat = with(clone $query)->sum('total_price_ex_vat');
            $order->total_vat = with(clone $query)->sum('marg_vat');
            $order->total_true_profit = with(clone $query)->whereIn('status', [Stock::STATUS_PAID, Stock::STATUS_SOLD])->sum('true_profit');

            $order->total_profit = with(clone $query)->whereIn('status', [Stock::STATUS_PAID, Stock::STATUS_SOLD])->sum('profit');


//            $order->est_profit=$estProfit;
//            $order->est_profit_pre=$estProfitPre;

            //   $order->profit_ratio = $order->sold_sales_price > 0 ? $order->net_purchase_price/$order->sold_sales_price*100 : 0;

            // $order->sold_profit =  $order->sold_sales_price - $order->net_purchase_price;
            $order->sold_profit = $order->sold_sales_price - $order->net_purchase_price;
            $order->sold_profit_ratio = $order->sold_sales_price > 0 ? $order->sold_profit / $order->sold_sales_price * 100 : 0;
            $order->repair_cost = $exRepairCost + $order->total_part_cost;
            $order->total_b2b_qty = $b2bQty;
            $order->total_retail_qty = $retailQty;
            //  $order->vat_margin=($order->sold_sales_price-($order->total_purchase_price-$order->total_items_returned_value))*16.67/100;

            $order->vat_margin = with(clone $query)->whereIn('status', [Stock::STATUS_PAID, Stock::STATUS_SOLD])->sum('marg_vat');

            //  if($order->vat_type==="Margin") {
//                $order->true_profit = $order->sold_profit - $order->vat_margin;
//            }else{
//                $order->true_profit = $order->sold_profit;
//            }

            $order->profit_ratio = $order->sold_sales_price > 0 ? $order->true_profit / $order->sold_sales_price * 100 : 0;
            $order->delete_lost = with(clone $query)->whereIn('status', [Stock::STATUS_DELETED, Stock::STATUS_LOST])->count();

            $countries = with(clone $query)->select('purchase_country')->groupBy('purchase_country')->get();
            $order->country = $countries->count() == 1 ? $country = $countries->first()->purchase_country_flag : "";
        }

        if ($request->sort && $request->sortO) {
            if ($request->sortO == 'DESC') {
                $orders->sortByDesc($request->sort);
            } else {
                $orders->sortBy($request->sort);
            }
        }

//        if($request->ajax()) {
//            return response()->json(['resultHtml' => view('stock.purchase-orders-all-list', compact('orders'))->render()]);
//        }


        if ($request->ajax()) {
            return response()->json([
                'itemsHtml' => View::make('stock.purchase-orders-all-list', compact('orders', 'reitem_unsold'))->render(),
                'paginationHtml' => $orders->appends($request->all())->render()
            ]);
        }

        return view('stock.purchase-orders-all', compact('orders', 'reitem_unsold'));

    }


    /**
     * @param Request $request
     * @return void
     */
    public function exportCsvPurchaseOrdersAll(Request $request)
    {

        ini_set('max_execution_time', 1000);
        ini_set('memory_limit', '5024M');
        $query = Stock::where('vendor_name', "Zapper");
        $orders = Stock::where('purchase_order_number', '<>', '')->orderBy('purchase_date', 'desc')->groupBy('purchase_order_number');
        $reitem_unsold = null;
        //   $orders = $orders->get();


        $fields = [
            'PO Number' => 'purchase_order_number',
            'Supplier' => 'supplier',
            'Date' => 'date',
            'No. Items' => 'no_items',
            'Sales Price' => 'total_sales_price',
            'Sales Price (ex VAT - VATable)' => 'sales_price_ex_vat',
            'Device Purchase Cost' => 'device_purchase_cost',
            'Total Purchase Cost' => 'total_purchase_cost',
            'Vat Type' => 'vat_type',
            'Items Sold' => 'items_sold',
            'Items Returned' => 'items_returned',
            'Value of Items Returned' => 'value_of_items_returned',
            'Net Purchase Price' => 'net_purchase_price',
            'Profit' => 'profit',
            'Vat Margin' => 'vat_margin',
            'True Profit' => 'total_true_profit',
            'Profit Ratio %' => 'profit_ratio',
            'Seller Fees' => 'shipping_cost',
            'Est Net Profit' => 'est_profit',
            'Est Net Profit %' => 'est_profitPre',
            'Items In Stock' => 'items_to_sell',
            'Est Sales Price for unsold' => 'total_sales_stock_price',
            'Qty Repaired' => 'items_in_repair',
            'Total Repair Cost' => 'repair_cost',
            'Qty Unlocked' => 'total_unlock_qty',
            'Total Unlocking Cost' => 'total_unlock_cost',
            'Qty sold B2B' => 'total_b2b_qty',
            'Qty Sold Retail' => 'total_retail_qty',
            'Lost/Deleted' => 'delete_lost'
        ];


        $csvPath = tempnam('/tmp', 'stock-full-export-');
        unlink($csvPath);
        $csvPath .= '.csv';
        $fh = fopen($csvPath, 'w');
        fputcsv($fh, array_keys($fields));


        if ($request->start && $request->end) {

            $orders->whereBetween('purchase_date', [$request->start . ' 00:00:00', $request->end . ' 23:59:59']);
            // $orders->where('supplier_id', $request->supplier_id);
        }


        $orders->chunk(500, function ($stock) use ($fields, $fh) {
            foreach ($stock as $item) {
                $estProfit = 0;
                $query = Stock::where('purchase_order_number', $item->purchase_order_number);
                $item->total_sales_price = with(clone $query)->whereIn('status', [Stock::STATUS_PAID, Stock::STATUS_SOLD])->sum('sale_price');
                if ($item->vat_type === "Margin") {
                    $item->sold_sales_price = with(clone $query)->whereIn('status', [Stock::STATUS_PAID, Stock::STATUS_SOLD])->sum('sale_price');
                    $item->total_sales_stock_price = with(clone $query)->whereIn('status', [Stock::STATUS_IN_STOCK, Stock::STATUS_BATCH, Stock::STATUS_RETAIL_STOCK, Stock::STATUS_READY_FOR_SALE, Stock::STATUS_RE_TEST, Stock::STATUS_ALLOCATED, Stock::STATUS_INBOUND, Stock::STATUS_LISTED_ON_AUCTION, Stock::STATUS_RESERVED_FOR_ORDER, Stock::STATUS_REPAIR])->sum('sale_price');
                    //  $item->total_sales_price=with(clone $query)->whereIn('status', [Stock::STATUS_PAID,Stock::STATUS_SOLD])->sum('sale_price');
                } else {
                    $item->sold_sales_price = with(clone $query)->whereIn('status', [Stock::STATUS_PAID, Stock::STATUS_SOLD])->sum('total_price_ex_vat');
                    $item->total_sales_stock_price = with(clone $query)->whereIn('status', [Stock::STATUS_IN_STOCK, Stock::STATUS_BATCH, Stock::STATUS_RETAIL_STOCK, Stock::STATUS_READY_FOR_SALE, Stock::STATUS_RE_TEST, Stock::STATUS_ALLOCATED, Stock::STATUS_INBOUND, Stock::STATUS_LISTED_ON_AUCTION, Stock::STATUS_RESERVED_FOR_ORDER, Stock::STATUS_REPAIR])->sum('total_price_ex_vat');
                    //$order->total_sales_price=with(clone $query)->whereIn('status', [Stock::STATUS_PAID,Stock::STATUS_SOLD])->sum('total_price_ex_vat');
                }

                $exRepairCost = 0;
                foreach (with(clone $query)->with('repair_item')->get() as $repair) {
                    if (count($repair->repair_item)) {
                        foreach ($repair->repair_item as $items) {
                            if ($items->estimate_repair_cost > 0) {

                                $exRepairCost += $items->estimate_repair_cost;

                            }

                        }
                    }
                }

                $b2bQty = 0;
                $retailQty = 0;
                $totalOfShipping = 0;
                foreach (with(clone $query)->with('sale_history')->whereIn('status', [Stock::STATUS_PAID, Stock::STATUS_SOLD])->get() as $sale_his) {

                    if (count($sale_his->sale_history) > 0) {
                        if ($sale_his->purchase_order_number === "29119") {
                            $userId = $sale_his->sale_history[1]->user_id;
                        } else {
                            $userId = $sale_his->sale_history[0]->user_id;
                        }
                        $user = User::find($userId);

                        if (isset($user->quickbooks_customer_category)) {
                            if (in_array($user->quickbooks_customer_category, ['B2B Device Sales'])) {
                                $b2bQty++;
                            } else if (in_array($user->quickbooks_customer_category, ['Consumer Device Sales', 'eBay Sales', 'Backmarket Sales', 'Misc Sales', 'Rest of World Device Sales'])) {
                                $retailQty++;
                            }
                        }
                    }

                    if (!is_null($sale_his->sale)) {

                        $totalOfShipping += $sale_his->sale->platform_fee + $sale_his->sale->shipping_cost;
                        $shippingCostAvg = ($sale_his->sale->platform_fee + $sale_his->sale->shipping_cost) / count($sale_his->sale->stock);

                        $delivery_charges = 0;
                        $finalShippingCost = 0;

                        if (!is_null($sale_his->sale->delivery_charges)) {
                            $delivery_charges = ($sale_his->sale->delivery_charges / 1.2) / count($sale_his->sale->stock);
                        }

                        //   dd($order);
                        $finalShippingCost = $shippingCostAvg - $delivery_charges;

                        $estProfit += $sale_his->true_profit - $finalShippingCost;
                        //  $estProfit=';
                    }
                }


                $item->items_sold = with(clone $query)->whereIn('status', [Stock::STATUS_SOLD, Stock::STATUS_PAID])->count();
                $item->total_purchase_price = with(clone $query)->sum('purchase_price');
                $item->total_purchase_price_cost = with(clone $query)->sum('purchase_price') + with(clone $query)->sum('part_cost') + with(clone $query)->sum('unlock_cost') + $exRepairCost;
                $item->items_to_sell = with(clone $query)->whereIn('status', [Stock::STATUS_IN_STOCK, Stock::STATUS_BATCH, Stock::STATUS_RETAIL_STOCK, Stock::STATUS_READY_FOR_SALE, Stock::STATUS_RE_TEST, Stock::STATUS_ALLOCATED, Stock::STATUS_INBOUND, Stock::STATUS_LISTED_ON_AUCTION, Stock::STATUS_RESERVED_FOR_ORDER, Stock::STATUS_REPAIR])->count();
                $item->items_returned = with(clone $query)->whereIn('status', [Stock::STATUS_RETURNED_TO_SUPPLIER])->count();
                if ($item->vat_type === "Margin") {
                    $item->total_items_returned_value = with(clone $query)->whereIn('status', [Stock::STATUS_RETURNED_TO_SUPPLIER])->sum('sale_price');
                } else {
                    $item->total_items_returned_value = with(clone $query)->whereIn('status', [Stock::STATUS_RETURNED_TO_SUPPLIER])->sum('total_price_ex_vat');
                }
                $item->total_return_supplier_purchas = with(clone $query)->whereIn('status', [Stock::STATUS_RETURNED_TO_SUPPLIER])->sum('purchase_price');
                $item->items_in_repair = with(clone $query)->whereIn('status', [Stock::STATUS_REPAIR])->count();
                $item->items_in_retest = with(clone $query)->whereIn('status', [Stock::STATUS_RE_TEST])->count();
                $item->items_listed_on_auction = with(clone $query)->whereIn('status', [Stock::STATUS_LISTED_ON_AUCTION])->count();
                $item->items_reserved_for_order = with(clone $query)->whereIn('status', [Stock::STATUS_RESERVED_FOR_ORDER])->count();
                $item->items_3rd_party = with(clone $query)->whereIn('status', [Stock::STATUS_3RD_PARTY])->count();
                $item->supplier = !is_null($item->supplier_id) ? $item->supplier->name : '-';
                $item->net_purchase_price = $item->total_purchase_price_cost - $item->total_return_supplier_purchas;
                //    $item->profit =  $item->total_sales_price - $item->total_purchase_price;
                $item->shipping_cost = $totalOfShipping;
                $item->total_price_ex_vat = $item->total_sales_price / 1.2;
                $item->total_vat = with(clone $query)->sum('marg_vat');
                $item->total_true_profit = with(clone $query)->whereIn('status', [Stock::STATUS_PAID, Stock::STATUS_SOLD])->sum('true_profit');
                $item->total_profit = with(clone $query)->whereIn('status', [Stock::STATUS_PAID, Stock::STATUS_SOLD])->sum('profit');
                $item->sold_profit = $item->sold_sales_price - $item->net_purchase_price;
                $item->sold_profit_ratio = $item->sold_sales_price > 0 ? $item->sold_profit / $item->sold_sales_price * 100 : 0;
                $item->repair_cost = $exRepairCost + $item->total_part_cost;
                $item->total_b2b_qty = $b2bQty;
                $item->total_retail_qty = $retailQty;
                $item->vat_margin = with(clone $query)->whereIn('status', [Stock::STATUS_PAID, Stock::STATUS_SOLD])->sum('marg_vat');
                //    $item->profit_ratio = $item->sold_sales_price > 0 ? $item->true_profit / $item->sold_sales_price * 100 : 0;
                $item->delete_lost = with(clone $query)->whereIn('status', [Stock::STATUS_DELETED, Stock::STATUS_LOST])->count();
                $item->total_unlock_qty = with(clone $query)->where('unlock_cost', '>', 0)->count();
                $item->total_unlock_cost = with(clone $query)->sum('unlock_cost');


//                $countries = with(clone $query)->select('purchase_country')->groupBy('purchase_country')->get();
//                $item->country = $countries->count() == 1 ? $country = $countries->first()->purchase_country_flag : "";


                //      $item->supplier = !is_null($item->supplier)|| !isset($item->supplier) ? $item->supplier->name:'-';
                $item->date = $item->purchase_date;
                $item->no_items = with(clone $query)->count();
                $item->total_sales_price = $item->total_sales_price;
                $item->sales_price_ex_vat = $item->vat_type === Stock::VAT_TYPE_STD ? money_format($item->total_sales_price / 1.2) : 'N/A';
                $item->device_purchase_cost = $item->total_purchase_price;
                $item->total_purchase_cost = $item->total_purchase_price_cost;
                $item->vat_type = $item->vat_type;
                $item->items_sold = $item->items_sold;
                $item->items_returned = $item->items_returned;
                $item->value_of_items_returned = $item->total_return_supplier_purchas;
                $item->profit = $item->vat_type === Stock::VAT_TYPE_STD ? money_format(($item->total_sales_price / 1.2) - $item->net_purchase_price)
                    : money_format(($item->total_sales_price - $item->net_purchase_price));


                if ($item->vat_type === Stock::VAT_TYPE_STD) {
                    $TrProfit = ($item->total_sales_price / 1.2) - $item->net_purchase_price;

                } else {
                    $TrProfit = $item->total_true_profit;


                }

                $item->total_true_profit = $item->vat_type === Stock::VAT_TYPE_STD ? money_format($TrProfit) : $TrProfit;
                //  $item->est_profit = $item->total_true_profit-  $item->shipping_cost;


                if ($TrProfit > 0) {
                    $item->profit_ratio = $item->vat_type === Stock::VAT_TYPE_STD ? number_format(($TrProfit / ($item->total_sales_price / 1.2)) * 100, 2)
                        : number_format(($TrProfit / $item->total_sales_price) * 100, 2);
                }


                $item->shipping_cost = $item->shipping_cost;

                if ($item->vat_type === Stock::VAT_TYPE_STD) {
                    $Ety = ($item->total_sales_price / 1.2) - $item->net_purchase_price;

                } else {
                    $Ety = $item->total_true_profit;
                }

                $item->est_profit = $Ety - $item->shipping_cost;

                //      $item->est_profitPre = $item->total_sales_price > 0  ? number_format(($Ety-$item->shipping_cost / $item->total_sales_price)*100,2) .'%' : '';

                if ($Ety > 0) {
                    $item->est_profitPre = $item->vat_type === Stock::VAT_TYPE_STD ? number_format(($Ety - $item->shipping_cost) / ($item->total_sales_price / 1.2) * 100, 2) . "%"
                        : number_format(($Ety - $item->shipping_cost) / ($item->total_sales_price) * 100, 2);
                }


                //  $item->est_profitPre ='estPRE';


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
        return back();


    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function getPurchaseOverview()
    {
        $items = Stock::where('name', '<>', '')->orderBy('name', 'desc')->groupBy('name')->get();
        foreach ($items as $item) {
            $query = Stock::where('name', $item->name);
            $item->items = with(clone $query)->count();
            $item->total_sales_price = with(clone $query)->sum('sale_price');
            $item->total_purchase_price = with(clone $query)->sum('purchase_price');
            $item->profit = $item->total_sales_price - $item->total_purchase_price;
            $item->items_to_sell = with(clone $query)->whereIn('status', [Stock::STATUS_IN_STOCK, Stock::STATUS_BATCH])->count();
        }
        $items = collect($items);
        $items = $items->sortByDesc('items');
        return view('stock.purchase-overview', compact('items'));
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
     */
    public function getPurchaseOverviewStats(Request $request)
    {
        $stats = [];
        $items_sold = 0;
        $items_to_sell = 0;
        if ($request->name) {
            $stats['name'] = $request->name;
            $query = Stock::where('name', $request->name);
            $count = with(clone $query)->count();
            if ($count) {
                $stats['total'] = $count;

                $items_to_sell = with(clone $query)->whereIn('status', [Stock::STATUS_IN_STOCK, Stock::STATUS_BATCH])->get();
                $items_sold = with(clone $query)->whereIn('status', [Stock::STATUS_PAID, Stock::STATUS_SOLD])->get();

                $stats['purchase_price'] = with(clone $query)->sum('purchase_price');
                $stats['sales_price'] = with(clone $query)->sum('sale_price');
                $stats['gross_profit'] = $stats['sales_price'] - $stats['purchase_price'];

                $stats['fully_working_no_touch_id'] = with(clone $query)->where('grade', Stock::GRADE_FULLY_WORKING_NO_TOUCH_ID)->count();
                $stats['fully_working'] = with(clone $query)->where('grade', Stock::GRADE_FULLY_WORKING)->count();
                $stats['minor_fault'] = with(clone $query)->where('grade', Stock::GRADE_MINOR_FAULT)->count();
                $stats['major_fault'] = with(clone $query)->where('grade', Stock::GRADE_MAJOR_FAULT)->count();
                $stats['broken'] = with(clone $query)->where('grade', Stock::GRADE_BROKEN)->count();

                $stats['condition_a'] = with(clone $query)->where('condition', Stock::CONDITION_A)->count();
                $stats['condition_b'] = with(clone $query)->where('condition', Stock::CONDITION_B)->count();
                $stats['condition_c'] = with(clone $query)->where('condition', Stock::CONDITION_C)->count();

                $stats['networks']['unlocked'] = with(clone $query)->where('network', 'Unlocked')->count();
                $stats['networks']['unknown'] = with(clone $query)->where('network', 'Unknown')->count();
                $stats['networks']['vodafone'] = with(clone $query)->where('network', 'Vodafone')->count();
                $stats['networks']['ee'] = with(clone $query)->where('network', 'EE')->count();
                $stats['networks']['three'] = with(clone $query)->where('network', 'Three')->count();
                $stats['networks']['o2'] = with(clone $query)->where('network', 'O2')->count();

                $other = $stats['total'];
                foreach ($stats['networks'] as $count) $other -= $count;
                $stats['networks']['other'] = $other;
            }
        } else {
            return back()->with('messages.error', 'Items not found');
        }

        return view('stock.purchase-overview-stats', compact('stats', 'items_sold', 'items_to_sell'));
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\JsonResponse
     */
    public function getReadyForSale(Request $request)
    {
        $items = Stock::where('status', Stock::STATUS_READY_FOR_SALE);
        $itemsSummary = Stock::where('status', Stock::STATUS_READY_FOR_SALE);
        if ($request->grade) {
            $items->where('grade', $request->grade);
            $itemsSummary->where('grade', $request->grade);
        }

        if ($request->network) {
            $items->where('network', $request->network);
            $itemsSummary->where('network', $request->network);
        }

        if ($request->term) {
            $items->where('name', 'like', "%$request->term%");
            $itemsSummary->where('name', 'like', "%$request->term%");
        }
        $items = $items->get();
        $itemsSummary = $itemsSummary->groupBy('name', 'capacity', 'grade')
            ->select(DB::raw('count(*) as quantity'), 'name', 'capacity', 'grade')
            ->orderBy('quantity', 'desc')->get();

        if ($request->ajax()) {
            return response()->json([
                'itemsHtml' => View::make('stock.ready-for-sale-list', compact('items', 'itemsSummary'))->render()
            ]);
        }

        return view('stock.ready-for-sale', compact('items', 'itemsSummary'));
    }


    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function getReadyForSaleExport()
    {
        return Excel::download(new ReadyForSaleExport(), 'readyforsales.csv');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\JsonResponse
     */
    public function getRetailStock(Request $request)
    {
        $items =Stock::where('status', Stock::STATUS_RETAIL_STOCK);
        $itemsSummary = Stock::where('status', Stock::STATUS_RETAIL_STOCK);
        $skuSummary = Stock::where('status', Stock::STATUS_RETAIL_STOCK)->where('new_sku', '!=', '');
        $missingSku = Stock::where('status', Stock::STATUS_RETAIL_STOCK)->where('new_sku', '');

        if ($request->has('show_missing_sku')) {
            $option = $request->show_missing_sku ? '=' : '!=';
            $items->where('new_sku', $option, '');
            $itemsSummary->where('new_sku', $option, '');
            $skuSummary->where('new_sku', $option, '');
        }

        if ($request->grade) {
            $items->where('grade', $request->grade);
            $itemsSummary->where('grade', $request->grade);
            $skuSummary->where('grade', $request->grade);
            $missingSku->where('grade', $request->grade);
        }

        if ($request->network) {
            $items->where('network', $request->network);
            $itemsSummary->where('network', $request->network);
            $skuSummary->where('network', $request->network);
            $missingSku->where('network', $request->network);
        }

        if ($request->term) {
            $items->where('name', 'like', "%$request->term%");
            $itemsSummary->where('name', 'like', "%$request->term%");
            $skuSummary->where('name', 'like', "%$request->term%");
            $missingSku->where('name', 'like', "%$request->term%");
        }
        if ($request->new_sku) {
            $items->where('new_sku', 'like', "%$request->new_sku%");
            $itemsSummary->where('new_sku', 'like', "%$request->new_sku%");
            $skuSummary->where('new_sku', 'like', "%$request->new_sku%");
            $missingSku->where('new_sku', 'like', "%$request->new_sku%");
        }
        $items = $items->get();
        $missingSku = $missingSku->get();
        $itemsSummary = $itemsSummary->groupBy('name', 'capacity', 'grade')
            ->select(DB::raw('count(*) as quantity'), 'name', 'capacity', 'grade')
            ->orderBy('quantity', 'desc')->get();

        $skuSummary = $skuSummary->groupBy('new_sku')
            ->select(DB::raw('count(*) as quantity'), 'new_sku')
            ->orderBy('quantity', 'desc')->get();

        if ($request->ajax()) {
            return response()->json([
                'itemsHtml' => View::make('stock.retail-stock-list', compact('items', 'itemsSummary', 'skuSummary', 'missingSku'))->render()
            ]);
        }

        return view('stock.retail-stock', compact('items', 'itemsSummary', 'skuSummary', 'missingSku'));
    }

    /**
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function getRetailStockExport()
    {
        return Excel::download(new RetailStockExport(), 'retailStock.csv');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postRemoveProductAssignment(Request $request)
    {
        $stock = Stock::findOrFail($request->id);
        $stock->product_id = null;
        $stock->non_serialised = 0;
        $stock->save();

        return back()->with('messages.success', "Product assignment has been removed");
    }


    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postItemReceive(Request $request)
    {
        $item = Stock::findOrFail($request->stock_id);

        if ($item->status != Stock::STATUS_INBOUND) {
            return back()->with('messages.error', "$item->our_ref - Wrong Status");
        }

        $item->status = Stock::STATUS_IN_STOCK;
        $item->save();

        StockLog::create([
            'stock_id' => $item->id,
            'user_id' => Auth::user()->id,
            'content' => 'This item was moved from Inbound to In stock by ' . Auth::user()->first_name
        ]);

        return back()->with('messages.success', "Item $item->our_ref status has been changed to 'In Stock'");
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postUpdateRetailStockQuantities(Request $request)
    {
        artisan_call_background('orderhub:update-retail-stock-quantities');

        return back()->with('messages.success', 'Cron updating retail stock quantities has been started');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRedirectBatch(Request $request)
    {
        $ids = [];

        foreach (Auth::user()->basket as $item) {
            $ids[$item->id] = true;
        }

        foreach ($request->ids ?: [] as $id) {
            $ids[$id] = true;
        }

        if (!$ids) {
            return response()->json([
                'status' => 'error',
                'message' => "You didn't select anything",
            ]);
        } else {
            return response()->json([
                'status' => 'success',
                'url' => route('stock.create-batch', ['ids' => array_keys($ids)]),
            ]);
        }
    }



    public function postChangeGrade(Request $request)
    {

        if (!$request->grade || !in_array($request->grade, Stock::getAvailableGradesWithKeys()))
            return back()->with('messages.error', 'Invalid Grade');
        $items = Stock::query()->whereIn('id', $request->ids)->get();

        $message = "";

        foreach ($items as $item) {
            if ($item->grade != $request->grade && $request->grade == Stock::GRADE_FULLY_WORKING && !$item->grade_fully_working_available_) {
                $message .= "<a href='" . route('stock.single', ['id' => $item->id]) . "'>$item->our_ref</a> - Battery life is $item->battery_life% and therefore this phone cannot be sold as fully working. Please replace the battery and then re-run through Phone Diagnostics.\n";
                continue;
            }
            $log = "Changed grade from $item->grade to $request->grade";
            $item->grade = $request->grade;
            $item->save();
          StockLog::create([
                'stock_id' => $item->id,
                'user_id' => Auth::user()->id,
                'content' => $log
            ]);
        }
        if ($message) {
            return back()->with('messages.error-custom', $message);
        }

        return back()->with('messages.success', 'Grade changed: ' . count($request->ids) . ' items - ' . $request->grade);
    }

    public function getStockStats()
    {
        $stats = [];
        $stats['In Stock'] = Stock::where('status', Stock::STATUS_IN_STOCK)->count();
        $stats['In Repair'] = Stock::where('status', Stock::STATUS_REPAIR)->count();
        $stats['Ready for Sale'] = Stock::where('status', Stock::STATUS_READY_FOR_SALE)->count();
        $stats['Retail Stock'] = Stock::where('status', Stock::STATUS_RETAIL_STOCK)->count();
        $stats['Listed on Auction'] = Stock::where('status', Stock::STATUS_LISTED_ON_AUCTION)->count();

        return view('stock.stats', compact('stats'));
    }
    public function postAssignProduct(Request $request)
    {
        $stock = Stock::findOrFail($request->stock_id);
        $product = Product::findOrFail($request->product_id);

        if ($product->archive) {
            return back()->with('messages.error', 'Product has been Archive So not Allowed to Assigned');
        }
        if (!$product->archive) {
            $stock->product_id = $product->id;

            if ($product->non_serialised) {
                $stock->non_serialised = 1;
            }
            $stock->save();
        }


        return back()->with('messages.success', 'Product has been assigned');
    }



}
