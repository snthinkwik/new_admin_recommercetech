<?php

namespace App\Http\Controllers;

use App\Models\AccessToken;
//use App\Commands\Dpd\CreateShipping;
//use App\Commands\ebay\CreateNewInvoice;
//use App\Commands\ebay\eBayOrderSync;
//use App\Commands\ebay\ImportDPDSync;
use App\Contracts\Invoicing;
use App\Contracts\TrgStock;
use App\Models\DeliveryNotes;
use App\Models\EbayFees;
use App\Models\EbayOrderItems;
use App\Models\EbayOrderLog;
use App\Models\EbayOrders;
use App\Models\EbayRefund;
use App\Models\HistoryLog;
use App\Models\Part;
use App\Models\Sale;
use App\Models\SalePart;
use App\Models\Stock;
use App\Models\StockLog;
use App\Models\SysLog;
use App\Models\User;
use App\Jobs\ebay\CreateNewInvoice;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use View;
use Illuminate\Support\Facades\Auth;

class EbayOrderController extends Controller
{
    public function index(Request $request) {

        //  dd($request->all());
        $ebayOrders = EbayOrders::with(["EbayOrderItems.stock","Newsale"])
            ->fromRequest($request);
        //->orderByRaw("CAST(sales_record_number AS SIGNED) DESC");


        if ($request->status) {
            $ebayOrders->where('status', $request->status);
        }
        if($request->platform){
            $ebayOrders->where('platform', $request->platform);
        }
        $ebayOrders = $ebayOrders->orderBy('id', 'DESC')->paginate(config('app.pagination'))
            ->appends(array_filter(array_reverse($request->all())));

        $countList = [];

        if ($request->ajax()) {
            return response()->json([
                'itemsHtml' => View::make('ebay-order.list', compact('ebayOrders', 'countList'))->render(),
                'paginationHtml' => '' . $ebayOrders->render(),
                'sort' => $request->sort,
                'sortO' => $request->sortO,
            ]);
        }

        $statusCount = EbayOrders::select(
            'id', DB::raw('COUNT(CASE WHEN `status`= "' . EbayOrders::STATUS_NEW . '" THEN 1 END) as total_new'), DB::raw('COUNT(CASE WHEN `status`= "' . EbayOrders::STATUS_DISPATCHED . '" THEN 1 END) as total_dispatched'), DB::raw('COUNT(CASE WHEN `status`= "' . EbayOrders::STATUS_CANCELLED . '" THEN 1 END) as total_cancelled'), DB::raw('COUNT(CASE WHEN `status`= "' . EbayOrders::STATUS_REFUNDED . '" THEN 1 END) as total_refunded'), DB::raw('COUNT(CASE WHEN `status`= "' . EbayOrders::STATUS_AWAITING_PAYMENT . '" THEN 1 END) as total_awaiting_payment')
        );

        $statusCount = $statusCount->get();

        return view('ebay-order.index', compact('ebayOrders', 'countList', 'statusCount'));
    }

    public function view($id) {

        $eBayOrder = EbayOrders::with('Newsale')->findOrFail($id);

        $countAuction = 0;
        $ItemSalePrice = 0;
        $totalFeeAmount = 0;
        $totalDeliveryFeeAmount = 0;

        return view('ebay-order.single',
            compact('eBayOrder',
                'countAuction',
                'totalFeeAmount',
                'totalDeliveryFeeAmount'
            )
        );
    }

    public function postBulkRetry(Request $request) {


        $EbayOrders = EbayOrders::whereIn('id', $request->ids);

        if(!in_array(Auth::user()->email, $this->getAllOrdersEmails())) {
            $EbayOrders->whereHas('EbayOrderItems', function($q) {
                $q->where('owner', EbayOrderItems::RECOMM);
            });
        }

        $EbayOrders = $EbayOrders->get();

        if ($EbayOrders->count() > 0) {
            foreach ($EbayOrders as $Order) {

                $Order->status = $request->status;

                $ChangeOwner = '';
                if ($Order->isDirty()) {
                    foreach ($Order->getAttributes() as $key => $value) {
                        if ($value !== $Order->getOriginal($key) && !checkUpdatedFields($value, $Order->getOriginal($key))) {
                            $orgVal = $Order->getOriginal($key);
                            $ChangeOwner .= "Changed \"$key\" from \"$orgVal\" to \"$value\".\n";
                        }
                    }
                }

                $Order->save();

                if (!empty($ChangeOwner)) {
                    $ebayOrdersLogModel = new   EbayOrderLog();
                    $ebayOrdersLogModel->orders_id = $Order->id;
                    $ebayOrdersLogModel->content = $ChangeOwner;
                    $ebayOrdersLogModel->save();
                }
            }
        }
    }

    public function getStats(Request $request) {
        /* $ebayOrders = EbayOrders::leftjoin('ebay_fees as ef', DB::raw('BINARY master_ebay_orders.sales_record_number'), '=', DB::raw('BINARY ef.sales_record_number'))
          ->select('master_ebay_orders.owner', 'master_ebay_orders.sales_record_number', DB::raw('count(*) as total_order'), 'total_price', DB::raw('SUM(master_ebay_orders.total_price) as total'), DB::raw("SUM(REPLACE(amount,'£','')) as total_ebay_fees"))
          ->where('status', EbayOrders::STATUS_DISPATCHED)
          ->where('owner', '!=', '')
          ->groupBy('owner'); */

        $start_date = "";
        $end_date = "";

        $ebayOrders = \App\EbayOrderItems::with("order")
            ->where('owner', '!=', '')
            ->whereHas('order', function($q) {
                $q->where('status', EbayOrders::STATUS_DISPATCHED);
            })
            ->select("order_id", "owner", DB::raw('count(*) as total_order'), DB::raw('SUM(individual_item_price) as total_price'))
            ->groupBy('owner');

        if(!in_array(Auth::user()->email, $this->getAllOrdersEmails())) {
            $ebayOrders->where('owner', EbayOrderItems::RECOMM);
        }

        $ebayOrdersUnassigned = \App\EbayOrderItems::with("order")
            ->where('owner', '=', '')
            ->whereHas('order', function($q) {
                $q->where('status', EbayOrders::STATUS_DISPATCHED);
            })
            ->select("order_id", "owner", DB::raw('count(*) as total_order'), DB::raw('SUM(individual_item_price) as total_price'))
            ->groupBy('owner');


        if ($request->start_date && $request->end_date) {
            $ebayOrders->whereHas('order', function($q) use($request) {
                $q->whereBetween('paid_on_date', [date('Y-m-d', strtotime($request->start_date)), date('Y-m-d', strtotime($request->end_date))]);
            });
            $ebayOrdersUnassigned->whereHas('order', function($q) use($request) {
                $q->whereBetween('paid_on_date', [date('Y-m-d', strtotime($request->start_date)), date('Y-m-d', strtotime($request->end_date))]);
            });
        }

        $ebayOrders = $ebayOrders->get();
        $ebayOrdersUnassigned = $ebayOrdersUnassigned->get();


        if ($request->ajax()) {
            $start_date = $request->start_date;
            $end_date = $request->end_date;
            return response()->json([
                'itemsHtml' => View::make('ebay-stats.list', compact('ebayOrders', 'start_date', 'end_date', 'ebayOrdersUnassigned'))->render(),
            ]);
        }

        return view('ebay-stats.index', compact('ebayOrders', 'start_date', 'end_date', 'ebayOrdersUnassigned'));
    }

    public function syncToEbayOrder(Request $request) {
        $user = Auth::user();
        Queue::pushOn('ebay', new eBayOrderSync($user));
        return back()->with('messages.success', 'eBay Orders are now importing, please refresh page after sometime.');
    }

    public function historyLog() {
        $historyLog = HistoryLog::orderBy("id", "DESC")
            ->paginate(config('app.pagination'));

        return view('ebay-order.history-log', compact('historyLog'));
    }

    public function updateOwner(Request $request) {

        $Item = \App\EbayOrderItems::where('item_id', $request['item_id'])->first();
        $Item->owner = $request->owner;

        $ChangeOwner = '';
        if ($Item->isDirty()) {
            foreach ($Item->getAttributes() as $key => $value) {
                if ($value !== $Item->getOriginal($key) && !checkUpdatedFields($value, $Item->getOriginal($key))) {
                    $orgVal = $Item->getOriginal($key);
                    $ChangeOwner .= "Changed \"$key\" from \"$orgVal\" to \"$value\".\n";
                }
            }
        }

        $Item->save();

        if (!empty($ChangeOwner)) {
            $ebayOrdersLogModel = new \App\EbayOrderLog();
            $ebayOrdersLogModel->orders_id = $Item->order_id;
            $ebayOrdersLogModel->content = $ChangeOwner;
            $ebayOrdersLogModel->save();
        }
    }

    public function updateSaleType(Request $request) {

        $Item = \App\EbayOrderItems::where('item_id', $request['item_id'])->first();
        $Item->sale_type = $request->sale_type;

        $ChangeOwner = '';
        if ($Item->isDirty()) {
            foreach ($Item->getAttributes() as $key => $value) {
                if ($value !== $Item->getOriginal($key) && !checkUpdatedFields($value, $Item->getOriginal($key))) {
                    $orgVal = $Item->getOriginal($key);
                    $ChangeOwner .= "Changed \"$key\" from \"$orgVal\" to \"$value\".\n";
                }
            }
        }

        $Item->save();

        if (!empty($ChangeOwner)) {
            $ebayOrdersLogModel = new \App\EbayOrderLog();
            $ebayOrdersLogModel->orders_id = $Item->order_id;
            $ebayOrdersLogModel->content = $ChangeOwner;
            $ebayOrdersLogModel->save();
        }
    }

    public function updateStatus(Request $request) {

        $EbayOrder = EbayOrders::findOrFail($request->id);
        $EbayOrder->status = $request->status;

        $ChangeOwner = '';
        if ($EbayOrder->isDirty()) {
            foreach ($EbayOrder->getAttributes() as $key => $value) {
                if ($value !== $EbayOrder->getOriginal($key) && !checkUpdatedFields($value, $EbayOrder->getOriginal($key))) {
                    $orgVal = $EbayOrder->getOriginal($key);
                    $ChangeOwner .= "Changed \"$key\" from \"$orgVal\" to \"$value\".\n";
                }
            }
        }

        $EbayOrder->save();

        if (!empty($ChangeOwner)) {
            $ebayOrdersLogModel = new \App\EbayOrderLog();
            $ebayOrdersLogModel->orders_id = $EbayOrder->id;
            $ebayOrdersLogModel->content = $ChangeOwner;
            $ebayOrdersLogModel->save();
        }
    }

    public function ready_for_invoice(Request $request) {
        $OrderItem = EbayOrderItems::with("fees", "order", "matched_to_item", "DpdInvoice")
            ->readyForInvoice()
            ->fromRequest($request)
            ->paginate(config('app.pagination'))
            ->appends(array_filter(array_reverse($request->all())));

        if ($request->ajax()) {
            return response()->json([
                'itemsHtml' => View::make('ebay-order.ready_for_invoice', compact('OrderItem'))->render(),
                'paginationHtml' => '' . $OrderItem->render(),
                'sort' => $request->sort,
                'sortO' => $request->sortO,
            ]);
        }

        $SalesPrice = \App\EbayOrderItems::with("fees", "order")
            ->whereHas('order', function($q) {
                $q->where("status", EbayOrders::STATUS_DISPATCHED);
            })
            ->whereNull("invoice_number")
            ->has("fees", ">", 0)
            ->where("owner", \App\EbayOrderItems::RECOMM)
            ->sum("individual_item_price");

        $PayPalPrice = \App\EbayOrders::with("EbayOrderItems")
            ->whereHas('EbayOrderItems', function($q) {
                $q->where("owner", \App\EbayOrderItems::RECOMM);
            })
            ->whereIn("status", [EbayOrders::STATUS_DISPATCHED, EbayOrders::STATUS_REFUNDED, EbayOrders::STATUS_CANCELLED])
            ->whereNotNull("paypal_fees")
            ->sum("paypal_fees");


        $PackaingMaterial = \App\EbayOrders::with("EbayOrderItems")
            ->whereHas('EbayOrderItems', function($q) {
                $q->where("owner", \App\EbayOrderItems::RECOMM);
            })
            ->whereIn("status", [EbayOrders::STATUS_DISPATCHED, EbayOrders::STATUS_REFUNDED, EbayOrders::STATUS_CANCELLED])
            ->whereNotNull("packaging_materials")
            ->sum("packaging_materials");

        $Fees = EbayFees::with("order_items")
            ->whereHas('order_items', function($q) {
                $q->where("owner", \App\EbayOrderItems::RECOMM)
                    ->whereNull("invoice_number");
            })
            ->where("matched", "Yes")
            ->sum(\DB::raw("REPLACE(amount,'£','')"));

//        $ManualFees = \App\ManualEbayFeeAssignment::where("owner", \App\EbayOrderItems::RECOMM)
//                ->whereNull("invoice_number")
//                ->sum(\DB::raw("REPLACE(amount,'£','')"));

        $totalRefundAmount = \App\EbayRefund::sum('refund_amount');

        $totalDeliveryCharge = \App\DpdInvoice::whereIn("matched", array_map('current', \App\EbayOrders::with("EbayOrderItems")
            ->whereHas('EbayOrderItems', function($q) {
                $q->where("owner", \App\EbayOrderItems::RECOMM);
            })
            ->select("sales_record_number")
            ->get()
            ->toArray()))
            ->sum("cost");

        $totalEbayDeliveryCharge = \App\EbayDeliveryCharges::with(["order.EbayOrderItems"])
            ->whereHas('order.EbayOrderItems', function($q) {
                $q->where("owner", \App\EbayOrderItems::RECOMM);
            })
            ->sum("cost");

        return view('ebay-order.index_ready_for_invoice', compact('OrderItem', 'SalesPrice', 'Fees', 'totalDeliveryCharge', 'PayPalPrice', 'totalEbayDeliveryCharge', 'totalRefundAmount'));
    }

    public function eBayFeeAssigment(Request $request) {

        $ManuallyAssignFee = \App\ManualEbayFeeAssignment::where('owner', \App\EbayOrderItems::RECOMM)
            ->fromRequest($request)
            ->paginate(config('app.pagination'))
            ->appends(array_filter(array_reverse($request->all())));

        if ($request->ajax()) {

            return response()->json([
                'itemsHtml' => View::make('ebay-fee-manual-assigned.list', compact('ManuallyAssignFee'))->render(),
                'paginationHtml' => '' . $ManuallyAssignFee->render(),
                'sort' => $request->sort,
                'sortO' => $request->sortO,
            ]);
        }

        $totalManualFees = \App\ManualEbayFeeAssignment::where("owner", \App\EbayOrderItems::RECOMM)
            ->whereNull("invoice_number")
            ->sum(\DB::raw("REPLACE(amount,'£','')"));

        return view('ebay-fee-manual-assigned.index', compact('ManuallyAssignFee', 'totalManualFees'));
    }

    public function export_ready_for_invoice_csv() {

        $OrderItem = EbayOrderItems::with("fees", "order", "matched_to_item")
            ->has("fees", ">", 0)
            ->where("owner", EbayOrderItems::RECOMM)->whereHas('order', function ($q) {
                $q->whereNotIn('status', [EbayOrders::STATUS_REFUNDED, EbayOrders::STATUS_CANCELLED]);
            })->get();

        $ReadyForInvoiceList = [];

        foreach ($OrderItem as $item) {
            $ReadyForInvoiceList[] = [
                'Sales Record No' => $item->sales_record_number,
                'Sale Date' => date('d-m-Y', strtotime($item->order->sale_date)),
                'Item Name' => $item->item_name,
                'Item Number' => $item->external_id,
                'Custom Label' => $item->item_sku,
                'Quantity' => $item->quantity,
                'Item Price' => money_format($item->individual_item_price),
                'Sale Type' => $item->sale_type,
                'Order Status' => ucfirst($item->order->status)
            ];
        }

        $rBorder = "F";
        $filename = "Ready for Invoice - " . time();
        $count = count($ReadyForInvoiceList) + 1;
        $file = \Maatwebsite\Excel\Facades\Excel::create($filename, function ($excel) use ($ReadyForInvoiceList, $count, $rBorder) {
            $excel->setTitle('Items');
            $excel->sheet('Items', function ($sheet) use ($ReadyForInvoiceList, $count, $rBorder) {
                $sheet->fromArray($ReadyForInvoiceList);
                $sheet->setFontSize(10);
                // Left Border
                $sheet->cells('A1:A' . $count, function ($cells) {
                    $cells->setBorder('none', 'none', 'none', 'none');
                });
                // Right Border
                $sheet->cells($rBorder . '1:' . $rBorder . $count, function ($cells) {
                    $cells->setBorder('none', 'none', 'none', 'none');
                });
                // Top+Bottom border - first row
                $sheet->row(1, function ($row) {
                    $row->setBorder('none', 'none', 'none', 'none');
                    $row->setFontSize(11);
                });
                // Bottom border - last row
                $sheet->row($count, function ($row) {
                    $row->setBorder('none', 'none', 'none', 'none');
                });
            });
        });

        $file->download('xls');
        return back();
    }

    public function dpdImport(Request $request) {

        $this->validate($request, [
            'ebay-dpd' => 'required|mimes:csv,txt',
        ]);
        $path = $request->file('ebay-dpd')->getRealPath();
        config(['excel.import.startRow' => 5]);
        $data = Excel::load($path, function ($reader) {

        }, 'ISO-8859-1')->get();

        Queue::pushOn('ebay_dpd', new ImportDPDSync($data));
        return back()->with('messages.success', 'eBay DPD are now importing');
    }

    public function EbayRefund(Request $request) {

        $eBayRefund = EbayRefund::fromRequest($request)
            ->where(function (Builder $query) use($request) {
                if (!$request->ajax()) {
                    $query->where('owner', EbayOrderItems::RECOMM)
                        ->where('processed', 'No');
                }
            })
            ->paginate(config('app.pagination'))
            ->appends(array_filter(array_reverse($request->all())));

        if ($request->ajax()) {
            return response()->json([
                'itemsHtml' => View::make('ebay-refund.list', compact('eBayRefund'))->render(),
                'paginationHtml' => '' . $eBayRefund->render(),
                'sort' => $request->sort,
                'sortO' => $request->sortO,
            ]);
        }


        return view('ebay-refund.index', compact('eBayRefund'));
    }

    public function getInvoice($id, Invoicing $invoicing) {
        $invoicePath = $invoicing->getTech360InvoiceDocument($id);
        header('Content-type: application/pdf');
        readfile($invoicePath);
        die;
    }

    public function getCreditMemo($id, Invoicing $invoicing) {
        $creditMemoPath = $invoicing->getCreditMemoDocument($id);
        header('Content-type: application/pdf');
        readfile($creditMemoPath);
        die;
    }

    public function getInvoiceFees($id, TrgStock $trgStock) {
        $res = $trgStock->getInvoiceDocument($id);
        if($res->status == 'success') {
            header('Content-type: application/pdf');
            echo(base64_decode($res->data));
            die;
        }

        return back()->with('messages.error', 'Error: '.$res->data);
    }

    protected function getAllOrdersEmails()
    {
        return ['sam@recomm.co.uk'];//, 'radoslaw.kowalczyk@netblink.net'];
    }

    public function AssignToStock(Request $request){
        $this->validate($request, [
            'imei' => 'required',
        ]);

        if(strpos($request->imei, "RCT") !== false){
            $id = str_ireplace( 'RCT', '', $request->imei);

        }else{
            $id=$request->imei;
        }
        $stock=Stock::where('status','!=',Stock::STATUS_ALLOCATED)->where('imei',$request->imei)->orWhere('serial',$request->imei)->orWhere('id',$id)->orWhere('sku',$request->imei)->first();

        if(in_array($stock->status,[Stock::STATUS_SOLD,Stock::STATUS_ALLOCATED])){
            return['error'=>'Stock Already '.$stock->status];
        }
        $oldStock=getStockDetatils($stock->id);
        if(is_null($stock)){
            return['error'=>'Stock Already Assigned'];

        }

        $orderItem=EbayOrderItems::with('order')->find($request->eBay_order_id);

        $stockIds=[];
        $salePrice=[];
        $status=[];
        if($orderItem->quantity>1){


            if(!is_null($orderItem->stock_sale_price)){

                if(count(json_decode($orderItem->stock_sale_price,true))>0){
                    foreach (json_decode($orderItem->stock_sale_price,true) as $key=>$sale_price ){
                        $salePrice[$key]=$sale_price;
                    }
                }
            }


            if(!is_null($orderItem->stock_status)){
                if(count(json_decode($orderItem->stock_status,true))>0){
                    foreach (json_decode($orderItem->stock_status,true) as $key=>$stock_status ){
                        //    array_push($status,$stock_status);
                        $status[$key]=$stock_status;
                    }
                }
            }

            if(!is_null($orderItem->stock_id)){
                if(count(json_decode($orderItem->stock_id,true))>0){
                    foreach (json_decode($orderItem->stock_id,true) as $item ){
                        array_push($stockIds,$item);
                    }

                }
            }

            $salePrice[$stock->id]=$stock->sale_price;
            $status[$stock->id]=$stock->status;
            array_push($stockIds,$stock->id);
        }



        $taxRate = $orderItem->tax_percentage * 100 > 0 ? ($orderItem->tax_percentage) : 0;

        if ($orderItem->tax_percentage * 100 > 0 || !$orderItem->tax_percentage * 100  && $stock->vat_type==="Standard" ) {
            $vatType = "Standard";
        } else {
            $vatType = "Margin";

        }


        if($orderItem->quantity>1){
            // if()
            if( !is_null($orderItem->order)){
                if($orderItem->order->platform===Stock::PLATFROM_MOBILE_ADVANTAGE ||  $orderItem->order->platform===Stock::PLATFROM_EBAY){
                    $itemsPrice= $orderItem->individual_item_price;
                }else{
                    $itemsPrice= ($orderItem->individual_item_price)/$orderItem->quantity;
                }

            }else{
                $itemsPrice= ($orderItem->individual_item_price)/$orderItem->quantity;
            }

        }else{
            $itemsPrice= $orderItem->individual_item_price;
        }


        $totalCosts= $stock->total_cost_with_repair;
        $purchasePrice= $stock->purchase_price;
        $calculations = calculationOfProfitEbay($taxRate, $itemsPrice, $totalCosts, $vatType,$purchasePrice);
        if($stock){
            $stock->status=Stock::STATUS_ALLOCATED;
            $stock->sale_price=$itemsPrice;
            $stock->profit=$calculations['profit'];
            $stock->true_profit=$calculations['true_profit'];
            $stock->marg_vat=$calculations['marg_vat'];
            $stock->total_price_ex_vat=$calculations['total_price_ex_vat'];

            $stock->save();
        }
        if($stock->id){
            $orderItem->stock_id=$orderItem->quantity>1 ?json_encode(array_unique($stockIds)):$stock->id;
            $orderItem->stock_sale_price=$orderItem->quantity>1    ?json_encode($salePrice):$oldStock->sale_price;
            $orderItem->stock_status=$orderItem->quantity>1?  json_encode($status) :$oldStock->status;

        }

        $orderItem->save();



        return['success'=>'eBay order Assigned to Stock successfully'];
    }

    public function createBayInvoice(Request $request,Invoicing $invoicing){

        if($request->code != "784199"){
            return back()->with('messages.error', 'Invalid Authorisation Code');
        }

        $data=EbayOrders::with('EbayOrderItems')->findOrFail($request->id);
        $oldSale=Sale::where('buyers_ref',$data->order_id)->where('invoice_status','!=','voided')->first();
        if(!is_null($oldSale))
        {
            return back()->with('messages.error', 'Already Create Sale Order For this Retail Orders  Sales Order ID is '.$oldSale->id);
            exit();

        }


        if($data->order_id)
            if($data->platform===Stock::PLATFROM_EBAY){
                if($data->post_to_country==="United Kingdom" || $data->post_to_country==="Great Britain"){
                    $customerUser = User::where('invoice_api_id', env('QuickBookEbayUKId'))->firstOrFail();
                }else{
                    $customerUser = User::where('invoice_api_id', env('QuickBookEbayEUId'))->firstOrFail();
                }
            }else if($data->platform===Stock::PLATFROM_MOBILE_ADVANTAGE){
                $customerUser = User::where('invoice_api_id', env('QuickBookMobileAdvantage'))->firstOrFail();
            } else{
                if($data->platform===Stock::PLATFROM_RECOMM){
                    $user=User::find($data->user_id);
                    $customerUser = User::where('invoice_api_id', $user->invoice_api_id)->firstOrFail();
                }else{
                    if($data->post_to_country==="United Kingdom" || $data->post_to_country==="Great Britain"){
                        $customerUser = User::where('invoice_api_id', config('services.quickbooks.userid.backmarket.uk'))->firstOrFail();

                    }else{
                        $customerUser = User::where('invoice_api_id', env('services.quickbooks.userid.backmarket.eu'))->firstOrFail();
                    }
                }
            }

        if($customerUser->suspended) {
            return back()->with('messages.error', 'Customer is suspended');
        }
        $full_name=explode(' ',$data->buyer_name);

        if($data->post_to_country ==="United Kingdom" || $data->post_to_country ==="Great Britain"){
            $shippingCountry= str_replace(" ","",$data->post_to_country);
        }else{
            $shippingCountry=$data->post_to_country;
        }
        if($data->buyer_country==="United Kingdom" || $data->post_to_country ==="Great Britain"){
            $billingCountry=str_replace(" ","",$data->buyer_country);
        }else{
            $billingCountry=$data->buyer_country;
        }

        $customer = $customerUser->getCustomer($customerUser->invoice_api_id);

        $customer->first_name=$data->buyer_name;
        $customer->last_name='';
        // $customer->company_name=$data->shipping_address_company_name;
        $customer->shipping_address->line1=$data->post_to_address_1;
        $customer->shipping_address->line2=$data->post_to_address_2;
        $customer->shipping_address->city=$data->post_to_city;
        $customer->shipping_address->county=$data->post_to_county;
        $customer->shipping_address->postcode=$data->post_to_postcode;
        $customer->shipping_address->country=$shippingCountry;
        $customer->billing_address->line1=$data->buyer_address_1;
        $customer->billing_address->line2=$data->buyer_address_2;
        $customer->billing_address->city=$data->buyer_city;
        $customer->billing_address->county=$data->buyer_county;
        $customer->billing_address->postcode=$data->buyer_postcode;
        $customer->billing_address->country=$billingCountry;
        $invoicing->updateCustomer($customer);

        sleep(1);

        if($invoicing){
            $deliveryName = !empty($data['customer_is_collecting']) ? null : $invoicing->getDeliveryForUser($customerUser);

            $partsItemsBasket = Auth::user()->part_basket;

            if(!is_null($partsItemsBasket)) {
                foreach ($partsItemsBasket as $item) {
                    $part = Part::where('id', $item->part_id)->first();
                    if ($part->quantity < $item->quantity) {
                        return redirect('basket')->with('messages.warning', "While you were creating sale some of the requested parts have been sold. Sorry for your inconvenience");
                    }
                    if($part->sale_price <= 0) {
                        return redirect('basket')->with('messages.warning', "Price cannot be null");
                    }
                }
            }

            $lockKey = substr('sale_' . md5(rand()), 0, 32);
            $ids=[];
            foreach ($data->EbayOrderItems as $item){

                if($item->quantity>1)
                    foreach (json_decode($item->stock_id) as $stockId){
                        $ids[]=$stockId;
                    }else{
                    $ids[]=$item->stock->id;
                }

            }
            if(count($ids)>0) {
                SysLog::log("Setting lock_key to \"$lockKey\".", Auth::user()->id, $ids);
                Stock::whereIn('id', $ids)->where('locked_by', '')->update(['locked_by' => $lockKey]);
                $countLocked = Stock::whereIn('id', $ids)->where('locked_by', $lockKey)->count();

                $items = Stock::whereIn('id', $ids)->get();
            }
            $sale = new Sale();
            $sale->user_id = $customerUser->id;
            $sale->created_by = Auth::user()->id;
            $sale->customer_api_id = $customerUser->invoice_api_id;
            $sale->buyers_ref = $data->order_id ? $data->order_id:'';
            $sale->save();
            $data->new_sale_id=$sale->id;
            $data->save();
            $deliveryNote = DeliveryNotes::firstOrNew([
                'sales_id' => $sale->id
            ]);
            $deliveryNote->sales_id=$sale->id;
            $deliveryNote->billing_address=json_encode($customer->billing_address);
            $deliveryNote->shipping_address=json_encode($customer->shipping_address);
//        $deliveryNote->customer_name=$customer->first_name.' '.$customer->last_name;
            $deliveryNote->company_name=$customer->last_name;
            $deliveryNote->order_ref=$sale->buyers_ref;
            $deliveryNote->save();
            // add stock log when create sale
            if(count($ids)>0)
            {
                $items = Stock::whereIn('id', $ids)->get();
                if(count($items) > 0)
                {
                    foreach ($items as $item) {
                        $customerName = $customerUser->first_name. " ".$customerUser->last_name;
                        // $item_price = $data['items'][$item->id]['price'];
                        $item_price = $item->sale_price;
                    }
                }
            }

            $partsItems = [];

            if(!is_null($partsItemsBasket)) {
                foreach ($partsItemsBasket as $item) {
                    $partsItems[] = new SalePart([
                        'part_id' => $item->part->id,
                        'quantity' => $item->quantity,
                        'snapshot_name' => $item->part->name,
                        'snapshot_colour' => $item->part->colour,
                        'snapshot_type' => $item->part->type,
                        'snapshot_sale_price' => $item->part->sale_price
                    ]);

                    $part = Part::where('id', $item->part->id)->first();
                    $part->quantity -= $item->quantity;
                    $part->save();
                }
                $sale->parts()->saveMany($partsItems);
            }

            if(count($ids)>0) {
                $items = Stock::whereIn('id', $ids)->get();
                foreach ($items as $item) {
                    $item_price = $item->sale_price;
                    $item->status = Stock::STATUS_SOLD;
                    $item->original_sale_price = !is_null($data['items']) ? $data['items'][$item->id]['price']:0.00;
                    $item->sale_price = $item_price;
                    $item->sold_at = new Carbon;
                    $item->sale()->associate($sale);
                    $item->sale_history()->sync([$sale->id], false);
                    $item->save();
                    $content = "This device has been sold for £".$item_price." on ".date('m-d-Y')." to ".$customerName;
                    StockLog::create(['stock_id' => $item->id, 'user_id' => Auth::user()->id, 'content' => $content]);
                    if (isset($data['items'][$item->id]['unlock'])) {
                        $unlock = new Unlock();
                        $unlock->fill([
                            'imei' => $item->imei,
                            'network' => $item->network,
                        ]);
                        $unlock->stock_id = $item->id;
                        if (!empty($item->sale->user_id)) {
                            $unlock->user_id = $item->sale->user_id;
                        }
                        $unlock->save();
                    }
                }

                Auth::user()->basket()->sync([]);

            }
            if(!is_null($customerUser->quickbooks_customer_category)){
                $vatType=$items[0]['vat_type'];
                $customerLocation=$customerUser->location;
                if($data->platform===Stock::PLATFROM_MOBILE_ADVANTAGE){
                    $saleName= getQuickBookServiceProductNameForMobileAdvantage($customerUser->quickbooks_customer_category,$vatType);
                }else{
                    $saleName= getQuickBookServiceProductName($customerUser->quickbooks_customer_category,$vatType,$customerLocation,$data->platform);
                }

            }else{
                $saleName=$invoicing->getSaleForUser($customerUser);
            }

//            Queue::pushOn(
//                'ebay-invoices',
//                new CreateNewInvoice(
//                    $sale,
//                    $data,
//                    $customerUser,
//                    $saleName,
//                    $deliveryName,
//                    $data->tracking_number
//                )
//            );

            dispatch(new CreateNewInvoice(
                $sale,
                $data,
                $customerUser,
                $saleName,
                $deliveryName,
                $data->tracking_number
            ));
        }

        //Queue::pushOn('emails', new UnlockEmail($sale));
        return Auth::user()->type === 'user'
            ? view('sales.select-payment-method', compact('sale'))
            : redirect()->route('sales')->with('sales.created_id', $sale->id);

    }

    public function UnassignedToStock(Request $request){
        $ebayItem=EbayOrderItems::findOrFail($request->id);
        if($ebayItem->quantity>1){
            foreach (json_decode($ebayItem->stock_id) as $stockId){
                $stock=Stock::find(getStockDetatils($stockId)->id);
                $vatCalation=calculationOfProfit($stock->sale_price,$stock->total_cost_with_repair,$stock->vat_type,$stock->purchase_price);

                //$stock->status=$ebayItem->stock_status;
                $stock->profit=$vatCalation['profit'];
                $stock->true_profit=$vatCalation['true_profit'];
                $stock->marg_vat=$vatCalation['marg_vat'];
                $stock->sale_vat=$vatCalation['sale_vat'];
                $stock->total_price_ex_vat=$vatCalation['total_price_ex_vat'];
                $stock->save();
            }


            if(count(json_decode($ebayItem->stock_status))>0){
                foreach (json_decode($ebayItem->stock_status) as $key=>$status){

                    $stockStatus=Stock::find(getStockDetatils($key)->id);
                    $stockStatus->status=$status;
                    $stockStatus->save();
                }

            }

            if(count(json_decode($ebayItem->stock_sale_price))>0){

                foreach (json_decode($ebayItem->stock_sale_price) as $key=>$salePrice){

                    $stockSales=Stock::find(getStockDetatils($key)->id);
                    $stockSales->sale_price=$salePrice;
                    $stockSales->save();
                }
            }






            $ebayItem->stock_id=NULL;
            $ebayItem->save();


        }
        else{
            if($ebayItem){


                $stock=Stock::find($ebayItem->stock_id);
                $stock->status=$ebayItem->stock_status;
                $stock->sale_price=$ebayItem->stock_sale_price;
                $stock->save();

                $ebayItem->stock_id=NULL;
                $ebayItem->save();
            }



        }


        return "successfully unassigned Stock";



    }
    public function updateRate(Request $request){

        $ebayItem=EbayOrderItems::findOrFail($request->id);
        $ebayItem->tax_percentage=$request->rate;
        $ebayItem->save();
        return "successfully updated";

    }

    public function getUserAccessToken(Request $request){
        if($request->code){

            $content = "grant_type=authorization_code&code=".$_REQUEST['code']."&redirect_uri=".config('services.ebay.RU_Name');
            $header = ebayBasicToken(config('services.ebay.client_id'),config('services.ebay.client_secret'));

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://api.ebay.com/identity/v1/oauth2/token",
                CURLOPT_HTTPHEADER => $header,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $content
            ));
            $response = curl_exec($curl);


            $data= (array) json_decode($response);
            $accessToken=AccessToken::firstOrNew([
                'platform'=>'ebay'
            ]);

            $accessToken->platform='ebay';
            $accessToken->access_token=$data['access_token'];
            $accessToken->expires_in=$data['expires_in'];
            $accessToken->refresh_token=$data['refresh_token'];
            $accessToken->refresh_token_expires_in=$data['refresh_token_expires_in'];
            $accessToken->token_type=$data['token_type'];
            $accessToken->save();

            return redirect(route('admin.settings.ebay'))->with('message.success','successfully added access token');

        }




    }

    public function GeneratedNewAccessToken(){

        $accessToken=AccessToken::where('platform','ebay')->first();
        $headers=ebayBasicToken(config('services.ebay.client_id'),config('services.ebay.client_secret'));
        $newAccessToken= getEbayRefreshTokenBaseToken($headers,$accessToken->refresh_token);
        $accessToken->access_token=$newAccessToken['access_token'];
        $accessToken->expires_in=$newAccessToken['expires_in'];
        $accessToken->save();

        return redirect(route('admin.settings.ebay'))->with('message.success','successfully added access token');


    }


    public function getUserAccessTokenSecond(Request $request){

        if($request->code){

            $content = "grant_type=authorization_code&code=".$_REQUEST['code']."&redirect_uri=".config('services.ebay2.RU_Name');

            $authorization = base64_encode(config('services.ebay2.client_id').':'.config('services.ebay2.client_secret'));
            $header = array("Authorization: Basic {$authorization}", "Content-Type: application/x-www-form-urlencoded");


            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://api.ebay.com/identity/v1/oauth2/token",
                CURLOPT_HTTPHEADER => $header,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $content
            ));
            $response = curl_exec($curl);

            $data= (array) json_decode($response);
            $accessToken=AccessToken::firstOrNew([
                'platform'=>'ebay-second'
            ]);

            $accessToken->platform='ebay-second';
            $accessToken->access_token=$data['access_token'];
            $accessToken->expires_in=$data['expires_in'];
            $accessToken->refresh_token=$data['refresh_token'];
            $accessToken->refresh_token_expires_in=$data['refresh_token_expires_in'];
            $accessToken->token_type=$data['token_type'];
            $accessToken->save();

            return redirect(route('admin.settings.ebay'))->with('message.success','successfully added access token');

        }




    }


    public function GeneratedNewAccessTokenSecond(){

        $accessToken=AccessToken::where('platform','ebay-second')->first();


        $authorization = base64_encode(config('services.ebay2.client_id').':'.config('services.ebay2.client_secret'));
        $headers = array("Authorization: Basic {$authorization}", "Content-Type: application/x-www-form-urlencoded");

        $newAccessToken= getEbayRefreshTokenBaseToken($headers,$accessToken->refresh_token);

        $accessToken->access_token=$newAccessToken['access_token'];
        $accessToken->expires_in=$newAccessToken['expires_in'];
        $accessToken->save();

        return redirect(route('admin.settings.ebay'))->with('message.success','successfully added access token');


    }

    public function getUserAccessTokenThird(Request $request){




        if($request->code){

            $content = "grant_type=authorization_code&code=".$_REQUEST['code']."&redirect_uri=".config('services.ebay3.RU_Name');

            $authorization = base64_encode(config('services.ebay3.client_id').':'.config('services.ebay3.client_secret'));
            $header = array("Authorization: Basic {$authorization}", "Content-Type: application/x-www-form-urlencoded");




            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://api.ebay.com/identity/v1/oauth2/token",
                CURLOPT_HTTPHEADER => $header,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $content
            ));
            $response = curl_exec($curl);


            $data= (array) json_decode($response);


            if(isset($data['error'])){
                return $data['error_description'];
            }

            $accessToken=AccessToken::firstOrNew([
                'platform'=>'ebay-third'
            ]);

            $accessToken->platform='ebay-third';
            $accessToken->access_token=$data['access_token'];
            $accessToken->expires_in=$data['expires_in'];
            $accessToken->refresh_token=$data['refresh_token'];
            $accessToken->refresh_token_expires_in=$data['refresh_token_expires_in'];
            $accessToken->token_type=$data['token_type'];
            $accessToken->save();

            return redirect(route('admin.settings.ebay'))->with('message.success','successfully added access token');

        }

    }


    public function GeneratedNewAccessTokenThird(){

        $accessToken=AccessToken::where('platform','ebay-third')->first();


        $authorization = base64_encode(config('services.ebay3.client_id').':'.config('services.ebay3.client_secret'));
        $headers = array("Authorization: Basic {$authorization}", "Content-Type: application/x-www-form-urlencoded");

        $newAccessToken= getEbayRefreshTokenBaseToken($headers,$accessToken->refresh_token);

        $accessToken->access_token=$newAccessToken['access_token'];
        $accessToken->expires_in=$newAccessToken['expires_in'];
        $accessToken->save();

        return redirect(route('admin.settings.ebay'))->with('message.success','successfully added access token');

    }


    public function getUserAccessTokenForth(Request $request){




        if($request->code){

            $content = "grant_type=authorization_code&code=".$_REQUEST['code']."&redirect_uri=".config('services.ebay4.RU_Name');

            $authorization = base64_encode(config('services.ebay4.client_id').':'.config('services.ebay4.client_secret'));
            $header = array("Authorization: Basic {$authorization}", "Content-Type: application/x-www-form-urlencoded");




            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://api.ebay.com/identity/v1/oauth2/token",
                CURLOPT_HTTPHEADER => $header,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $content
            ));
            $response = curl_exec($curl);


            $data= (array) json_decode($response);


            if(isset($data['error'])){
                return $data['error_description'];
            }

            $accessToken=AccessToken::firstOrNew([
                'platform'=>'ebay-forth'
            ]);

            $accessToken->platform='ebay-forth';
            $accessToken->access_token=$data['access_token'];
            $accessToken->expires_in=$data['expires_in'];
            $accessToken->refresh_token=$data['refresh_token'];
            $accessToken->refresh_token_expires_in=$data['refresh_token_expires_in'];
            $accessToken->token_type=$data['token_type'];
            $accessToken->save();

            return redirect(route('admin.settings.ebay'))->with('message.success','successfully added access token');

        }

    }


    public function GeneratedNewAccessTokenForth(){

        $accessToken=AccessToken::where('platform','ebay-forth')->first();


        $authorization = base64_encode(config('services.ebay4.client_id').':'.config('services.ebay4.client_secret'));
        $headers = array("Authorization: Basic {$authorization}", "Content-Type: application/x-www-form-urlencoded");

        $newAccessToken= getEbayRefreshTokenBaseToken($headers,$accessToken->refresh_token);

        $accessToken->access_token=$newAccessToken['access_token'];
        $accessToken->expires_in=$newAccessToken['expires_in'];
        $accessToken->save();

        return redirect(route('admin.settings.ebay'))->with('message.success','successfully added access token');

    }

    public function createShipping(){


        Queue::pushOn(
            'dpd-shipping',
            new CreateShipping()
        );
    }

    public function  updateEmailAndPhone(Request $request){
        $masterEbay=EbayOrders::find($request->id);

        if($request->email){
            $masterEbay->buyer_email=$request->email;
        }
        if($request->phone_number){
            $masterEbay->billing_phone_number=$request->phone_number;
        }
        $masterEbay->save();

        return back()->with('message.success','Contact Information successfully updated');


    }
}
