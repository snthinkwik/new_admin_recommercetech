<?php

namespace App\Http\Controllers;

use App\Exports\CusReturnExport;
use App\Exports\CUstomerReturnExport;
use App\Models\CustomerReturn;
use App\Models\CustomerReturnItems;
use App\Models\EbayOrders;
use App\Models\NewSalesStock;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\StockLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\View;

class CustomerReturnController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */

    public function index(Request $request)
    {

        $customerReturn = CustomerReturn::with(['sales','customerReturnsItems.stock'])
            ->select('*', DB::raw("SUM(total_sales_value_ex_vat) as total_sales"),
                DB::raw("SUM(total_purchase_cost_of_return_ex_vat) as total_purchase"))
            ->groupBy('sales_id')->orderBy('id', 'DESC')->fromRequest($request);


        if ($request->status) {
            if($request->status ==="All"){

                $customerReturn->whereNotIn('return_status', ['Completed','Returned to Customer','Credited']);
                // $customerReturn->whereNotNull('return_status');
            }else{
                $customerReturn->where('return_status', $request->status);
            }
        }else{

            $customerReturn->whereNotIn('return_status', ['Completed','Returned to Customer','Credited']);
        }

        $customerReturn = $customerReturn->paginate(config('app.pagination'))
            ->appends(array_filter(array_reverse($request->all())));
        if ($request->ajax()) {
            return response()->json([
                'itemsHtml' => View::make('customer-return.create-customer-list', compact('customerReturn'))->render(),
                'paginationHtml' => '' . $customerReturn->render(),
                'sort' => $request->sort,
                'sortO' => $request->sortO,
            ]);
        }


        return view('customer-return.create-customer-return-index', compact('customerReturn'));
    }

    public function create()
    {
        return view('customer-return.create-customer-return');
    }

    public function customerReturnCreate(Request $request)
    {

        $newStock = NewSalesStock::whereHas('sales', function($q){

            $q->whereNotIn('invoice_status',['voided']);

        })->whereIn('stock_id', $request->ids)->first();



        foreach ($request->ids as $id) {
            $newStock = NewSalesStock::whereHas('sales', function($q){

                $q->whereNotIn('invoice_status',['voided']);

            })->where('stock_id', $id)->first();

            $sales = Sale::find($newStock->sale_id);

            $ebayOrder = EbayOrders::where('new_sale_id', $newStock->sale_id)->first();

            $stock = Stock::find($id);
            $customerReturn = new CustomerReturn();
            $customerReturn->customer_name = !is_null($ebayOrder) ? $ebayOrder->post_to_name:$sales->user()->first()->first_name.' '. $sales->user()->first()->last_name;
            $customerReturn->sold_on_platform = !is_null($sales->platform) ? $sales->platform : '';
            $customerReturn->total_sales_value_ex_vat = 0;
            $customerReturn->buyers_ref =$sales->buyers_ref;
            $customerReturn->total_purchase_cost_of_return_ex_vat = 0;
            $customerReturn->returns_tracking_ref = $sales->tracking_number;
            $customerReturn->return_status = CustomerReturn::STATUS_RMA_ISSUED;
            $customerReturn->notes = $request->note;
            $customerReturn->date_return_received = $request->date_return_received;
            $customerReturn->date_credited = $request->date_credited;
            $customerReturn->qb_credit_note_ref = $request->qb_credit_note_ref;
            $customerReturn->date_of_issue = $request->date_of_issue;
            $customerReturn->reason_for_the_return = $request->reason_for_the_return;
            $customerReturn->sales_id = $sales->id;
            $customerReturn->tracking_ref = $request->tracking_ref;
            $customerReturn->save();


            $customerReturnItems = CustomerReturnItems::firstOrNew([
                'customer_return_id' => $customerReturn
            ]);

            $customerReturnItems->customer_return_id = $customerReturn->id;
            $customerReturnItems->name = $stock->make . ' ' . $stock->name;
            $customerReturnItems->purchase_cost = $stock->total_cost_with_repair;
            $customerReturnItems->sale_price = $stock->sale_price;
            $customerReturnItems->return_reason = $request->reason_for_the_return;
            $customerReturnItems->sale_id = $sales->id;
            $customerReturnItems->qb_invoice_id = $sales->invoice_number;
            $customerReturnItems->stock_id=$stock->id;
            $customerReturnItems->save();


            $newCustomerReturnItems = CustomerReturnItems::where('customer_return_id', $customerReturn->id);
            $sumTotalPurchase = $newCustomerReturnItems->sum('purchase_cost');
            $sumTotalSales = $newCustomerReturnItems->sum('sale_price');

            $newCustomerReturn = CustomerReturn::find($customerReturn->id);
            $newCustomerReturn->total_sales_value_ex_vat = $sumTotalSales;
            $newCustomerReturn->total_purchase_cost_of_return_ex_vat = $sumTotalPurchase;


            $newCustomerReturn->save();

        }

        return redirect(route('customer.return.index'))->with('message.success', 'Successfully Create Customer Return');
    }

    public function getCustomerReturnItem($id)
    {
        $customerReturnItem = CustomerReturnItems::where('sale_id', $id)->get();
        return view('customer-return.create-customer-item', compact('customerReturnItem'));
    }

    public function changeStockStatus($id)
    {
        $customerReturn = CustomerReturn::find($id);

        $sale = Sale::find($customerReturn->sales_id);


        if (!is_null($sale)) {

            $customerReturnItems=CustomerReturnItems::where('sale_id',$customerReturn->sales_id)->get();

            foreach ($customerReturnItems as $stock){


                $updateStock = Stock::find($stock->stock_id);
                if(!is_null($updateStock)){
                    $updateStock->status = Stock::STATUS_IN_STOCK;
                    $updateStock->sale_id = null;
                    $updateStock->save();

                    $newSalesStock = NewSalesStock::where('stock_id', $stock->stock_id)->get();


                    if(count($newSalesStock)){
                        foreach ($newSalesStock as $itemSale){
                            $itemDelete=NewSalesStock::find($itemSale->id);
                            $itemDelete->delete();
                        }

                    }

                    $user = Auth::user() ? Auth::user()->id : null;
                    $content = "Customer Return:- Item Removed from Sale. Status Changed from " . Stock::STATUS_SOLD . " to " . Stock::STATUS_IN_STOCK;
                    StockLog::create(['stock_id' => $updateStock->id, 'content' => $content, 'user_id' => $user]);
                }




            }


        }


        $customerReturn->return_status = 'Completed';
        if(!is_null($sale)){
            $customerReturn->buyers_ref =$sale->buyers_ref;
        }

        $customerReturn->save();


        return redirect(route('customer.return.index'));

    }

    public function customerReturnSingle($id)
    {
        ini_set('memory_limit', '1024M');
        sleep(3);
        $customerReturn = CustomerReturn::find($id);

        return view('customer-return.customer-return-single', compact('customerReturn'));
    }

    public function customerReturnUpdate(Request $request)
    {

        $customerReturn = CustomerReturn::find($request->id);
        $customerReturn->date_of_issue = $request->date_of_issue;
        $customerReturn->reason_for_the_return = $request->reason_for_the_return;
        $customerReturn->date_return_received = $request->date_return_received;
        $customerReturn->date_credited = $request->date_credited;
        $customerReturn->qb_credit_note_ref = $request->qb_credit_note_ref;
        $customerReturn->return_status = $request->return_status;
        $customerReturn->notes = $request->note;
        $customerReturn->tracking_ref = $request->tracking_ref;
        $customerReturn->save();

        return back()->with('message.success', 'Customer Return SuccessFully Update');


    }

    public function getSoldDate(Request $request)
    {

        if (strpos($request->term, 'RCT') !== false) {
            $term = str_replace("RCT", "", $request->term);
        } elseif (strpos($request->term, 'rct') !== false) {
            $term = str_replace("rct", "", $request->term);
        } else {
            $term = $request->term;
        }

        $sales = Stock::select('id', 'imei', 'serial')->where('id', 'like', '%' . $term . '%')
            ->orWhere('imei', 'like', '%' . $term . '%')
            ->orWhere('serial', 'like', '%' . $term . '%')
            ->whereIn('status', [Stock::STATUS_SOLD, Stock::STATUS_PAID])->get();
        $list = [];
        $ids['results'] = [];
        foreach ($sales as $item) {
            $imei = !is_null($item->imei) ? $item->imei : $item->serial;
            $list[] = [

                'id' => $item->id,
                'text' => "RCT:-" . $item->id . " IMEI:-" . $imei
            ];


        }

        $ids['results'] = $list;
        return json_encode($ids);

    }

    public function getDeleteSaleData(){
        $delete=Sale::with('newSalesStock')->select('*')->onlyTrashed()->get();
        return view('delete_recored', compact('delete'));
    }

    public function exportCsv(Request $request)
    {

        return Excel::download(new CusReturnExport(), 'CustomerReturn.csv');
    }
    public function getAllCustomerReturn(){


        $customerReturn = CustomerReturn::get();

        foreach ($customerReturn as $ty){

            echo ">>>>>>>>>>>>>>>>>>>>>>>>>>>>";
            echo "<br>";
            echo "id:-". $ty->id;
            echo "<br>";
            echo "buyer ref:-".$ty->buyers_ref;
            echo "<br>";
            echo "SalesId:-". $ty->sales_id;
            echo "<br>";
            echo ">>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>";
        }

//        echo "<pre>";
//        print_r($customerReturn);

    }

}
