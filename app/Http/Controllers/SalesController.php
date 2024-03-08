<?php

namespace App\Http\Controllers;

use App\Jobs\EbayRefunds\CreateCreditNote;
use App\Models\Address;
use App\Models\Batch;
use App\Commands\Sales\EmailSend;
use App\Commands\Sales\OrderImeis;
use App\Commands\Sales\PaymentReceived;
use App\Commands\Sales\TrackingUpdated;
use App\Contracts\Invoicing;
use App\Events\Sale\Cancelled;
use App\Http\Requests\SaleChangeStatusRequest;
use App\Models\Invoice;
use App\Models\OtherRecycle;
use App\Jobs\Sales\InvoiceCustomOrderCreate;
use App\Models\EbayOrders;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SalePart;
use App\Models\Stock;
use App\Models\StockLog;
use App\Models\SysLog;
use App\Models\Unlock;
use App\Models\User;
use App\Validation\Sales\PricesValidator;
use App\Validation\Sales\SaleValidator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\CustomerReturns;
use Illuminate\Support\Facades\Auth;
use App\Models\DeliveryNotes;
use PDF;
use Session;
use App\Jobs\Sales\InvoiceCreate;

class SalesController extends Controller
{
    public function getIndex(Invoicing $invoicing, Request $request)
    {


        if (!$request->status)
            $request->status = Auth::user()->type === 'user' ? 'any' : 'open_paid_other_recycler';
        // needed for open&paid by default
        if ($request->status == 'open_paid_other_recycler' || !$request->status) {
            $salesQuery = Sale::with(['stock', 'ebay_orders'])->whereIn('invoice_status', ['open', 'paid'])->orWhere(function ($q) {
                return $q->whereNotNull('other_recycler')->whereIn('invoice_status', ['open', 'paid']);
            })->orderBy('id', 'desc');
        } elseif ($request->status == 'open_paid' || !$request->status)
            $salesQuery = Sale::with(['stock', 'ebay_orders'])->whereIn('invoice_status', ['open', 'paid'])->whereNull('other_recycler')->orderBy('id', 'desc');
        elseif ($request->status == 'any')
            if (Auth::user()->type === 'user')
                $salesQuery = Sale::with(['stock', 'ebay_orders'])->whereNotIn('invoice_status', ['voided'])->orderBy('id', 'desc');
            else
                $salesQuery = Sale::with(['stock', 'ebay_orders'])->orderBy('id', 'desc');
        elseif ($request->status == 'other_recycler')
            $salesQuery = Sale::with(['stock', 'ebay_orders'])->whereNotNull('other_recycler')->orderBy('id', 'desc');
        else
            $salesQuery = Sale::with(['stock', 'ebay_orders'])->status($request->status)->orderBy('id', 'desc');

        if (Auth::user()->type === 'user') {
            $salesQuery->where('customer_api_id', Auth::user()->invoice_api_id);
            $salesQuery->whereNull('other_recycler');
        }

        if ($request->imei) {
            $stock = Stock::where('imei', $request->imei)
                ->orWhere('id', substr($request->imei, 3))
                ->orWhere('serial', $request->imei)
                ->first();

            if ($stock) $salesQuery->where('id', $stock->sale_id);
        }
        if ($request->buyers_ref) {
            $salesQuery->where('buyers_ref', $request->buyers_ref);
        }

        if ($request->invoice_number) {
            $salesQuery->where('invoice_number', 'like', "%$request->invoice_number%");
        }

        if (Auth::user()->type === 'admin' && $request->name) {
            $userIds = User::where('first_name', 'like', "%$request->name%")->orWhere('last_name', 'like', "%$request->name%")->lists('id');
            $salesQuery->whereIn('user_id', $userIds);
        }

        if (Auth::user()->type === 'admin' && $request->postcode) {
            $salesQuery->whereHas('user.address', function ($query) use ($request) {
                $query->where('postcode', 'like', "%$request->postcode%");
            });
        }
//		$sales = $salesQuery->paginate(config('app.pagination'))->appends($request->all());
        $sales = $salesQuery->paginate(10)->appends($request->all());

        if (Auth::user()->type == 'admin') {
            $salesList = json_decode(json_encode($sales->items()));
            $ids = collect($salesList)->keyBy('customer_api_id')->pluck('customer_api_id')->toArray();

        }
        $customers = Auth::user()->type !== 'user' ? $invoicing->getRegisteredSelectedCustomers($ids)->keyBy('external_id') : null;
        if (Auth::user()->type !== 'user' && $customers->isEmpty()) {
            $customers = $invoicing->getCustomers()->keyBy('external_id');
            foreach ($customers as $customer => $val) {
                $user = User::where('invoice_api_id', $customer)->first();
                if (!$user) {
                    unset($customers[$customer]);
                }
            }
        }
        $saleJustCreated = session('sales.created_id') ? Sale::findOrFail(session('sales.created_id')) : null;
        if ($request->ajax()) {
            return [
                'itemsHtml' => view('sales.list', compact('sales', 'saleJustCreated', 'customers'))->render(),
                'paginationHtml' => '' . $sales->render(),
            ];
        }


        //$sales = $salesQuery->paginate(config('app.pagination'))->appends($request->all());

        return view('sales.index', compact('sales', 'saleJustCreated', 'customers'));
    }

    public function postSelectPaymentMethod(Request $request)
    {
        $user = Auth::user();
        $sale = $user->sales()->findOrFail($request->id);

        return view('sales.select-payment-method', compact('sale'));
    }

    public function postPay(Request $request)
    {
        if (Auth::user()->has_incorrect_country) {
            return $this->forceAccountCountryUpdate(true, 'sales');
        }

        return $this->pay($request->id);
    }

    protected function pay($saleId)
    {
        $user = Auth::user();
        $address = $user->address;
        $sale = $user->sales()->findOrFail($saleId);

        // add card processing fee if possible
        $invoice_details = $sale->invoice_details;

        if ($invoice_details && !$sale->card_processing_fee) {
            try {

                $customerUser = User::find($invoice_details->customerUser);
                $saleName = $invoice_details->saleName;
                $delivery = $invoice_details->deliveryName;

                $invoicing = app('App\Contracts\Invoicing');
                $invoicing->voidInvoice($sale);

                switch ($invoice_details->type) {
                    case Sale::INVOICE_TYPE_INVOICE_CREATE:
                    {
                        $batch = $invoice_details->batch;
                        $price = $invoice_details->price;
                        $auction = null;

                        Queue::pushOn('invoices', new InvoiceCreate($sale, $customerUser, $saleName, $delivery, $batch, $price, $auction, true));
                        break;
                    }
                    case Sale::INVOICE_TYPE_INVOICE_CUSTOM_ORDER_CREATE:
                    {
                        Queue::pushOn('invoices', new InvoiceCustomOrderCreate($sale, $customerUser, $saleName, true));
                        break;
                    }
                    /*case Sale::INVOICE_TYPE_INVOICE_EBAY_CREATE:
                        {
                            Queue::pushOn('invoices', new InvoiceEbayCreate($sale, $customerUser, $saleName));
                            break;
                        }
                    case Sale::INVOICE_TYPE_INVOICE_EPOS_CREATE:
                        {
                            Queue::pushOn('invoices', new InvoiceEposCreate($sale, $customerUser, $saleName));
                            break;
                        }
                    case Sale::INVOICE_TYPE_INVOICE_MIGHTY_DEALS_CREATE:
                        {
                            Queue::pushOn('invoices', new InvoiceMightyDealsCreate($sale, $customerUser, $saleName));
                            break;
                        }
                    case Sale::INVOICE_TYPE_INVOICE_ORDERHUB_CREATE:
                        {
                            Queue::pushOn('invoices', new InvoiceOrderhubCreate($sale, $customerUser, $saleName));
                            break;
                        }*/
                }

                $attempts = 0;
                do {
                    sleep(2);
                    $sale = $sale->fresh();
                    if ($sale->card_processing_fee)
                        break;
                } while ($attempts <= 10);
            } catch (Exception $e) {
                alert("Sale $sale->id - Adding Card Processing Fee Exception: " . $e);
            }
        }
        // end adding card processing fee

        $gateway = app('payment_gateway');


        $userData = [
            'firstName' => $user->first_name,
            'lastName' => $user->last_name,
            'billingAddress1' => $address->line1,
            'billingAddress2' => $address->line2,
            'billingCity' => $address->city,
            'billingPostcode' => $address->postcode,
            'billingState' => $address->county,
            'billingCountry' => $address->country_details->code2,
            'billingPhone' => $user->phone,
            'shippingAddress1' => $address->line1,
            'shippingAddress2' => $address->line2,
            'shippingCity' => $address->city,
            'shippingPostcode' => $address->postcode,
            'shippingState' => $address->county,
            'shippingCountry' => $address->country_details->code2,
            'shippingPhone' => $user->phone,
            'company' => $user->company_name,
            'email' => $user->email,
        ];
        $purchaseData = [
            'amount' => $sale->amount,
            'currency' => 'GBP',
            'card' => $userData,
            'notifyUrl' => route('sage.notify'),
            'description' => "Sale $sale->id.",
            'profile' => 'LOW',
        ];
        $sageResponse = $gateway->purchase($purchaseData)->send();


        if ($sageResponse->isRedirect()) {
            session(['sales.payment_pending' => [
                'sale_id' => $sale->id,
                'sageResponse' => $sageResponse,
            ]]);
            return redirect()->route('sales.pay');
        } else {
            alert(
                "Sale transaction creation error (sale id \"$sale->id\"): \n" .
                print_r($sageResponse->getData(), true)
            );

            $message = "An error occurred while initiating the transaction. We've been notified and will try to resolve the " .
                "issue as soon as possible.";
            if (isset($sageResponse->getData()['StatusDetail'])) {
                $error_message = $sageResponse->getData()['StatusDetail'];
                $message .= "\n ERROR: " . $error_message;
            }
            return redirect()->route('sales')->with(
                'messages.error', $message
            );
        }
    }

    public function postPaymentComplete(Invoicing $invoicing)
    {
        $pendingPayment = session('sales.payment_pending');

        $response = redirect()->route('sales');
        $sale = Sale::find($pendingPayment['sale_id']);
        $sale->invoice_status = Invoice::STATUS_PAID;
        $sale->save();
        Queue::pushOn('emails', new PaymentReceived($sale));
//			$invoicing->markInvoicePaid($sale);
        return $response->with('messages.success', "Invoice marked as paid.");

    }

    public function getPay()
    {
        $pendingPayment = session('sales.payment_pending');
        if (!$pendingPayment) {
            return redirect('sales')->with('No payment is pending at the moment.');
        }
        $sale = Sale::findOrFail($pendingPayment['sale_id']);
        if ($sale->user->location != User::LOCATION_UK && $sale->invoice_total_amount > 1000) {
            return view('sales.unable-to-pay');
        }
        return view('sales.pay', compact('pendingPayment', 'sale'));
    }

    public function getModify()
    {
        return view('sales.modify');
    }

    public function postSwapItem(Request $request)
    {
        $lockKey = substr('swap_' . md5(rand()), 0, 32);
        if (!trim($request->original_ref) || !trim($request->replace_ref)) {
            return back()->withInput()->with('messages.error', "Please fill in the input fields.");
        }
        Stock::multiRef($request->replace_ref)->where('locked_by', '')->update(['locked_by' => $lockKey]);
        $original = Stock::multiRef($request->original_ref)->first();
        $replacement = Stock::multiRef($request->replace_ref)->first();

        if ($replacement) {
            SysLog::log("Item locked for swapping with key \"$lockKey\".", Auth::user()->id, $replacement->id);
        }

        foreach (['Original' => $original, 'Replacement' => $replacement] as $name => $item) {
            if (!$item) {
                if ($replacement) {
                    $replacement->locked_by = '';
                    $replacement->save();
                }
                return back()->withInput()->with('messages.error', "$name item not found.");
            }
        }

        if (
            !in_array($replacement->status, [Stock::STATUS_INBOUND, Stock::STATUS_IN_STOCK]) ||
            $replacement->locked_by !== $lockKey
        ) {
            $replacement->locked_by = '';
            $replacement->save();
            return back()->withInput()->with('messages.error', "Replacement item has to be inbound or in stock");
        }

        if (!$original->sale_id) {
            $replacement->locked_by = '';
            $replacement->save();
            return back()->withInput()->with('messages.error', "Original item has to be sold.");
        }

        foreach (['status', 'sale_id', 'sold_at'] as $propName) {
            $replacement->$propName = $original->$propName;
        }
        $replacement->save();

        $original->sale->stock()->sync([$replacement->id], false);
        SysLog::log(
            "Item \"$original->id\" swapped for \"$replacement->id\". Returning original to Stock.",
            Auth::user()->id,
            [$original->id, $replacement->id]
        );
        $original->sale_history()->detach($original->sale->id);
        $original->returnToStock();

        return back()->with(
            'messages.success',
            "Device has been swapped on this order. Please remove all stickers from the original item and place in the returns box"
        );
    }

    public function postRemoveItem(Request $request)
    {
        if (!trim($request->ref)) {
            return back()->withInput()->with('messages.error', "Please fill in the ref field.");
        }

        $item = Stock::multiRef($request->ref)->first();

        if (!$item) {
            return back()->with('messages.error', "Device identified by \"$request->ref\" not found.");
        }

        if (!$item->sale) {
            return back()->with('messages.error', "Device identified by \"$request->ref\" is not sold.");
        }

        DB::table('new_sales_stock')->where('stock_id', $item->id)->delete();
        $item->returnToStock();

        return back()->with('messages.success', "Device has been removed from all sales and returned to stock");
    }

    public function postTrackingNumber(Request $request, Invoicing $invoicing)
    {
        $sale = Sale::findOrFail($request->sale_id);
        $sale->tracking_number = $request->number;
        $sale->courier = $request->courier;
        $sale->save();
        if (!$sale->other_recycler) {
            $ids = [$sale->customer_api_id];
            $customers = Auth::user()->type !== 'user' ? $invoicing->getRegisteredSelectedCustomers($ids)->keyBy('external_id') : null;
            if (Auth::user()->type !== 'user' && $customers->isEmpty()) {
                $customers = $invoicing->getCustomers()->keyBy('external_id');
                foreach ($customers as $customer => $val) {
                    $user = User::where('invoice_api_id', $customer)->first();
                    if (!$user) {
                        unset($customers[$customer]);
                    }
                }
            }
            Queue::pushOn('emails', new EmailSend($sale, EmailSend::EMAIL_TRACKING_NUMBER));
        }

        return response()->json([
            'status' => 'success',
            'newRowHtml' => View::make('sales.item', compact('sale', 'customers'))->render(),
        ]);
    }

    public function postDelete(Invoicing $invoicing, Request $request)
    {
        $sale = Sale::findOrFail($request->id);
        if ($sale->invoice_api_id) {
            $invoicing->deleteInvoice($sale);
        }
        $sale->delete();

        return redirect()->route('sales')->with('messages.success', "Sale deleted");
    }

    public function postCheckPaid()
    {
        $ps = `ps aux`;
        if (strpos($ps, 'artisan sales:check-paid') !== false) {
            return back()->with('messages.warning', "The payment reconciliation script is already running.");
        }

        $basePath = base_path();
        $cmd = "php $basePath/artisan sales:check-paid";
        exec("$cmd > /dev/null 2> /dev/null &");
        return back()->with(
            'messages.success',
            "Payment reconciliation script has been started. It'll check the invoices and change the status of the ones " .
            "that have been paid."
        );
    }

    public function postChangeStatus(SaleChangeStatusRequest $request, Invoicing $invoicing)
    {
        $sale = Sale::findOrFail($request->id);
        $currentDate = Carbon::now();

        // Sometimes there was no status in request, which caused some bugs
        if ($request->status)
            if ($request->status == "picked")
                $sale->picked = 1;
            elseif ($request->status == "unpicked")
                $sale->picked = 0;
            else
                $sale->invoice_status = $request->status;

        if ($request->status === "dispatched") {
            $sale->dispatch_date = $currentDate->toDateString('Y-m-d');
        }


        $sale->save();
        $ids = [$sale->customer_api_id];
        $customers = Auth::user()->type !== 'user' ? $invoicing->getRegisteredSelectedCustomers($ids)->keyBy('external_id') : null;
        if (Auth::user()->type !== 'user' && $customers->isEmpty()) {
            $customers = $invoicing->getCustomers()->keyBy('external_id');
            foreach ($customers as $customer => $val) {
                $user = User::where('invoice_api_id', $customer)->first();
                if (!$user) {
                    unset($customers[$customer]);
                }
            }
        }

        if (!$sale->other_recycler) {
            switch ($request->status) {
                case Invoice::STATUS_READY_FOR_DISPATCH:
                    Queue::pushOn('emails', new EmailSend($sale, EmailSend::EMAIL_READY_FOR_DISPATCH));
                    break;
                case Invoice::STATUS_DISPATCHED:
                    Queue::pushOn('emails', new EmailSend($sale, EmailSend::EMAIL_DISPATCHED));
                    break;
                case Invoice::STATUS_PAID_ON_INVOICE:
                    Queue::pushOn('emails', new EmailSend($sale, EmailSend::EMAIL_PAID_ON_INVOICE));
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Status changed',
            'newRowHtml' => View::make('sales.item', compact('sale', 'customers'))->render(),
        ]);
    }

    public function postSingleChangeStatus(SaleChangeStatusRequest $request)
    {
        $sale = Sale::findOrFail($request->id);
        // Sometimes there was no status in request, which caused some bugs
        if ($request->status)
            $sale->invoice_status = $request->status;
        $sale->save();

        if (!$sale->other_recycler) {
            switch ($request->status) {
                case Invoice::STATUS_READY_FOR_DISPATCH:
                    Queue::pushOn('emails', new EmailSend($sale, EmailSend::EMAIL_READY_FOR_DISPATCH));
                    break;
                case Invoice::STATUS_DISPATCHED:
                    Queue::pushOn('emails', new EmailSend($sale, EmailSend::EMAIL_DISPATCHED));
                    break;
            }
        }

        return back()->with('messages.success', 'Status has been changed.');
    }

    public function postSingleTrackingNumber(Request $request)
    {
        $sale = Sale::findOrFail($request->id);
        $sale->tracking_number = $request->number;
        $sale->courier = $request->courier;
        $sale->save();

        if (!$sale->other_recycler) {
            Queue::pushOn('emails', new EmailSend($sale, EmailSend::EMAIL_TRACKING_NUMBER));
        }

        return back()->with('messages.success', 'Tracking Number has been added');
    }

    public function getInvoice($saleId, Invoicing $invoicing)
    {
        $sale = Sale::findOrFail($saleId);
        $invoicePath = $invoicing->getInvoiceDocument($sale);
        header('Content-type: application/pdf');
        readfile($invoicePath);
        die;
    }

    public function getStatusCheck(Request $request)
    {
        $sales = Sale::whereIn('id', $request->ids)->get();
        $res = [];

        foreach ($sales as $sale) {
            $docNumber = !is_null($sale->invoice_doc_number) ? '-' . $sale->invoice_doc_number : '';
            $amount = 0;
            if ($sale->vat_type === "Margin") {
                if (!is_null('delivery_charges')) {
                    $amount = $sale->amount - $sale->delivery_charges * 20 / 100;
                }
            } else {
                $amount = $sale->amount;
            }

            $totalProfit = $sale->vat_type === "Standard" ? ($amount / 1.2) - $sale->total_cost : $amount - $sale->total_cost;
            $vatMar = ((($sale->total_sale_price - $sale->total_purchase_price) * 16.67) / 100);
            $totalTrueProfit = $sale->vat_type === "Standard" ? $totalProfit : $totalProfit - $vatMar;
            $estNetProfit = $totalTrueProfit - $sale->platform_fee - $sale->shipping_cost;

            if ($sale->vat_type === "Standard") {
                $estNetProfitPer = number_format(($estNetProfit) / ($sale->amount / 1.2) * 100, 2) . "%";
            } else {
                if (!is_null('delivery_charges')) {


                    $estNetProfitPer = $amount > 0 ? number_format($estNetProfit / $amount * 100, 2) . "%" : '';
                } else {

                    $estNetProfitPer = $amount > 0 ? number_format($estNetProfit / $sale->amount * 100, 2) . "%" : '';
                }
            }

            if ($sale->vat_type === "Standard") {
                $coreAmount = number_format($sale->amount / 1.2, 2);
            } else {

                if (!is_null('delivery_charges')) {

                    $coreAmount = $sale->amount - $sale->delivery_charges * 20 / 100;
                } else {
                    $coreAmount = $sale->amount;
                }


            }
            $totalProfitPre = 0;
            $totalTrueProfitPre = 0;
            if ($coreAmount > 0) {
                if ($sale->vat_type === "Standard") {
                    $totalProfitPre = number_format($totalProfit / $coreAmount * 100, 2) . "%";
                    $totalTrueProfitPre = number_format(($totalTrueProfit / $coreAmount * 100), 2) . "%";

                } else {
                    $totalProfitPre = number_format(($totalProfit / $coreAmount) * 100, 2) . "%";
                    $totalTrueProfitPre = number_format(($totalTrueProfit / $coreAmount) * 100, 2) . "%";
                }

            }
            $res[] = [
                'id' => $sale->id,
                'status' => $sale->invoice_creation_status_alt,
                'status_finished' => $sale->invoice_creation_status_finished,
                'invoice_link' => $sale->invoice_creation_status === 'success'
                    ? '<a target="_blank" href="' . route('sales.invoice', $sale->id) . '">' .
                    'Invoice #' .
                    $sale->invoice_number . $docNumber .
                    '</a>'
                    : null,
                'amount' => money_format($amount),
                'ex_vat' => $sale->amount && $sale->vat_type === "Standard" ? money_format($sale->amount / 1.2) : '-',
                'total_profit' => money_format($totalProfit),
                'profit_per' => $totalProfitPre,
                'true_profit' => money_format($totalTrueProfit),
                'true_profit_per' => $totalTrueProfitPre,
                'seller_fees' => $sale->platform_fee,
                'shipping_cost' => $sale->shipping_cost,
                'est_net_profit' => money_format($estNetProfit),
                'est_net_profit_per' => $estNetProfitPer,
                'vat_margin' => $sale->vat_type === "Margin" ? money_format($vatMar) : '-',
                'platform' => $sale->platform
            ];
        }
        return response()->json($res);
    }

    public function getSingle($id, Invoicing $invoicing)
    {
        $user = Auth::user();
        if ($user->type == 'user') {
            $sale = $user->sales()->findOrFail($id);
        } else {
            $sale = Sale::with(['stock'])->findOrFail($id);
        }
        $customer = $invoicing->getCustomer($sale->customer_api_id);
        return view('sales.single', compact('sale', 'customer'));
    }

    public function getExport($id)
    {
        $sale = Sale::findOrFail($id);

        $items = $sale->stock;

        foreach ($items as $item) {
            $stock[] = [
                'RCT Ref' => $item->our_ref,
                'Device Name' => $item->name,
                'Capacity' => $item->capacity_formatted,
                'Network' => $item->network,
                'Grade' => $item->grade,
                'IMEI' => $item->imei,
                'Engineer Notes' => $item->notes,
                'Sales price' => $item->sale_price_formatted
            ];
        }

        $count = count($stock) + 1;

        $filename = "Sale_items-$sale->id";
        $rBorder = "H";

        $file = Excel::create($filename, function ($excel) use ($stock, $count, $rBorder) {
            $excel->setTitle('Sale Items');
            $excel->sheet('Sale Items', function ($sheet) use ($stock, $count, $rBorder) {
                $sheet->fromArray($stock);
                $sheet->setFontSize(10);
                // Left Border
                $sheet->cells('A1:A' . $count, function ($cells) {
                    $cells->setBorder('none', 'none', 'none', 'medium');
                });
                // Right Border
                $sheet->cells($rBorder . '1:' . $rBorder . $count, function ($cells) {
                    $cells->setBorder('none', 'medium', 'none', 'none');
                });
                // Top+Bottom border - first row
                $sheet->row(1, function ($row) {
                    $row->setBorder('medium', 'medium', 'medium', 'medium');
                    $row->setFontSize(11);
                });
                // Bottom border - last row
                $sheet->row($count, function ($row) {
                    $row->setBorder('none', 'medium', 'medium', 'medium');
                });
            });
        });

        return $file->download();
    }

    public function postSendOrderImeis(Request $request)
    {
        $sale = Sale::with('stock')->findOrFail($request->id);
        Queue::pushOn('emails', new OrderImeis($sale));

        return back()->with('messages.success', "Email with IMEIs was sent.");
    }

    public function postCancel(Request $request, Invoicing $invoicing)
    {


        $sale = Sale::findOrFail($request->id);

        $productIds = [];
        try {


            $i = 0;
            if (count($sale->ebay_orders) > 0) {

                foreach ($sale->ebay_orders as $ebay) {

                    foreach ($ebay->EbayOrderItems as $item) {
                        $i++;
                        if ($item->quantity > 1) {

                            if (!is_null(json_decode($item->stock_id))) {
                                foreach (json_decode($item->stock_id) as $stockId) {


                                    if (!is_null(getStockDetatils($stockId)->product) > 0) {

                                        if (getStockDetatils($stockId)->product->non_serialised) {
                                            $productIds[$i . '-' . getStockDetatils($stockId)->product->id] = $item->quantity;

                                        }
                                    }


                                }
                            }

                        } else {
                            foreach ($item->stock()->get() as $stock) {

                                if (!is_null($stock->product)) {

                                    if ($stock->product->non_serialised) {
                                        $productIds[$i . '-' . $stock->product->id] = $item->quantity;

                                    }
                                }

                            }
                        }


                    }
                }

                foreach ($productIds as $key => $value) {
                    if ($sale->invoice_creation_status !== "error") {
                        $id = explode('-', $key);
                        $product = App\Product::find($id[1]);
                        $product->multi_quantity = ($product->multi_quantity + $value);
                        $product->save();
                    }


                }

            } else {

                foreach ($sale->stock as $item) {

                    if (!is_null($item->product) > 0) {

                        if ($sale->invoice_creation_status !== "error") {
                            $stock = Stock::find($item->id);
                            $stock->temporary_qty = Null;
                            $stock->save();
                            if ($item->product->non_serialised) {
                                $product = Product::find($item->product->id);
                                $product->multi_quantity = ($product->multi_quantity + $item->temporary_qty);
                                $product->save();

                            }
                        }
                    }
                }
            }


            if (!Auth::user()->canVoidSale($sale)) {
                return back()->with('messages.error', "This order can't be cancelled.");
            }
            $ebayOrder = EbayOrders::where('new_sale_id', $request->id)->first();


            if (!is_null($ebayOrder)) {
                $ebayOrder = EbayOrders::findOrFail($ebayOrder->id);
                if (!is_null($ebayOrder)) {
                    $ebayOrder->new_sale_id = NULL;
                    $ebayOrder->save();
                }
            }


            if (!$sale->other_recycler && $sale->invoice_api_id)
                $invoicing->voidInvoice($sale);
            $sale->invoice_status = Invoice::STATUS_VOIDED;
            $sale->save();


            if (!$sale->other_recycler)
                event(new Cancelled($sale));

        } catch (Exception $e) {

            return redirect()->route('sales')->with('messages.error', 'Sale cancelled.');
        }


        return redirect()->route('sales')->with('messages.success', 'Sale cancelled.');
    }

    public function getRedirect(Request $request)
    {
        $ids = [];

        foreach (Auth::user()->basket as $item) {
            $ids[$item->id] = true;
        }

        foreach ($request->ids ?: [] as $id) {
            $ids[$id] = true;
        }

        if ($ids) {

            $items = Stock::whereIn('id', array_keys($ids))->get();

            foreach ($items as $key => $item) {

                if ($item['status'] === Stock::STATUS_REPAIR) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "Cannot add to sale as Stock ID has open repair job",
                    ]);
                }

                if ($items[0]['vat_type'] !== $item['vat_type']) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "You cannot add items with different VAT Type to the same sale",
                    ]);
                }
            }
        }

        $parts = Auth::user()->part_basket;

        $sumAmount = Stock::whereIn('id', array_keys($ids))->sum('sale_price');

        if (!$ids && !count($parts)) {
            return response()->json([
                'status' => 'error',
                'message' => "You didn't select anything",
            ]);
        } elseif (Auth::user()->has_incorrect_country) {
            return $this->forceAccountCountryUpdate();
        } elseif (Auth::user()->type === 'user') {
            if ($ids)
                $items = array_combine(array_keys($ids), array_fill(0, count($ids), ''));
            else
                $items = [];
            return response()->json([
                'status' => 'success',
                'url' => route('sales.summary', ['items' => $items]),
            ]);
        } elseif ($request->option) {
            return response()->json([
                'status' => 'success',
                'url' => route('sales.new', ['ids' => array_keys($ids), 'option' => $request->option]),
            ]);
        } else {
            return response()->json([
                'status' => 'success',
                'url' => route('sales.new', ['ids' => array_keys($ids)]),
            ]);
        }
    }

    protected function forceAccountCountryUpdate($redirect = false, $redirectRoute = 'stock')
    {
        Session::flash(
            'messages.warning',
            "We need to update the country in your address. Please set it here and then you'll be able to complete your order"
        );
        Session::set('account.country.save_redirect', route($redirectRoute));

        return $redirect
            ? redirect()->route('account')
            : response()->json(['status' => 'success', 'url' => route('account')]);
    }

    public function getCreate(Request $request)
    {


        if (Session::get('set_ids')) {
            $items = Stock::whereIn('id', Session::get('set_ids'))->whereIN('status', [Stock::STATUS_IN_STOCK, Stock::STATUS_READY_FOR_SALE])->orderBy('name', 'desc')->orderBy('capacity', 'desc')->get();
        } else {
            $items = Stock::whereIn('id', $request->ids)->orderBy('name', 'desc')->orderBy('capacity', 'desc')->get();
        }
        if ($request->option)
            $option = $request->option;
        else
            $option = '';

        $parts = Auth::user()->part_basket;
        if (!count($items) && !count($parts)) {
            return back()->with('messages.warning', "Nothing found.");
        }
        return view('sales.create', compact('items', 'option', 'parts'));
    }

    public function getSummary(Request $request, Invoicing $invoicing)
    {


        $validator = new PricesValidator(app('translator'), $request->all());
        $data = $this->validateWithObject($request, $validator);

        $errorMessage = '';


        if (session()->get('pre_data')) {
            $data = session()->get('pre_data')['data'];
            $errorMessage = session()->get('pre_data')['validation_message'];
        }


        $customersForAutocomplete = [];
        $parts = Auth::user()->part_basket;
        if (!isset($data['items']) && count($parts) > 0) {
            $data['items'] = [];
        }
        if (isset($data['items'])) {
            $stock = Stock::whereIn('id', array_keys($data['items']))
                ->whereIn('status', Auth::user()->allowed_statuses_buying)
                ->get();
        }

        $message = "";

        $productQty = [];
        $j = 0;

        if (isset($stock)) {
            foreach ($stock as $item) {
                $j++;
                if (isset($data['items'][$item->id]['qty'])) {
                    $productQty[$j . "-" . $data['items'][$item->id]['qty']] = $item->product_id;
                }
            }
        }

        $sum = 0;
        $counts = array_count_values($productQty);

        $filtered = array_filter($productQty, function ($value) use ($counts) {
            return $counts[$value] > 1;
        });

        if (count($filtered) > 0) {
            $product_id = '';
            foreach ($filtered as $key => $value) {
                $qty = explode('-', $key);
                $sum += $qty[1];

                $product_id = $value;


            }

            $product = App\Product::find($product_id);
            if ($product->non_serialised) {
                if ($product->multi_quantity < $sum) {
                    return back()->with('messages.error', "The Product " . $product->product_name . " out of stock");
                }
            }


        } else {
            foreach ($productQty as $value => $id) {
                $product = Product::find($id);
                if ($product->non_serialised) {
                    $qty = explode('-', $value);
                    if ($product->multi_quantity < $qty[1]) {
                        return back()->with('messages.error', "The Product " . $product->product_name . " out of stock");

                    }
                }
            }
        }

        // remove all non numeric symbols
        if(count($data)){
            if (count($data['items'])) {
                foreach ($data['items'] as $key => $price) {
                    $data['items'][$key]['price'] = preg_replace("/[^0-9.]/", "", $data['items'][$key]['price']);
                }
            }
        }


        foreach (Auth::user()->basket as $item) {


            if(count($stock)){
                if (!in_array($item->id, $stock->pluck('id')->toArray())) {
                    Auth::user()->basket()->detach($item->id);
                }
            }

        }
        if (Auth::user()->type == 'admin' && $request->grade) {

            if (isset($stock)) {
                foreach ($stock as $item) {
                    if ($request->grade != $item->grade) {
                        $message = "Changed 'grade' from '$item->grade' to '$request->grade'\n";
                        $item->grade = $request->grade;
                        $item->save();
                        StockLog::create([
                            'user_id' => Auth::user()->id,
                            'stock_id' => $item->id,
                            'content' => $message
                        ]);
                    }
                }
            }

        }
        // update sales price
        if (Auth::user()->type == 'admin') {
            if (isset($stock)) {
                foreach ($stock as $item) {

                    if (isset($data['items'][$item->id]['qty'])) {
                        $item->temporary_qty = $data['items'][$item->id]['qty'];
                        $item->save();
                    }


                    if ($data['items'][$item->id]['price'] != $item->sale_price) {

                        $message = "Changed 'price' from '$item->sale_price' to '" . $data['items'][$item->id]['price'] . "'\n";
                        $item->sale_price = $data['items'][$item->id]['price'];


                        $totalCosts = $item->total_cost_with_repair;
                        if ($item->vat_type === "Standard" && $data['items'][$item->id]['price']) {

                            $calculations = calculationOfProfit($data['items'][$item->id]['price'], $totalCosts, $item->vat_type);
                            $item->sale_vat = $calculations['sale_vat'];
                            $item->total_price_ex_vat = $calculations['total_price_ex_vat'];
                            $item->profit = $calculations['profit'];
                            $item->true_profit = $calculations['true_profit'];
                            $item->marg_vat = $calculations['marg_vat'];


                        } else if ($data['items'][$item->id]['price']) {

                            $calculations = calculationOfProfit($data['items'][$item->id]['price'], $totalCosts, $item->vat_type, $item->purchase_price);
                            $item->marg_vat = $calculations['marg_vat'];
                            $item->profit = $calculations['profit'];
                            $item->true_profit = $calculations['true_profit'];
                            $item->sale_vat = $calculations['sale_vat'];
                            $item->total_price_ex_vat = $calculations['total_price_ex_vat'];

                        }

                        StockLog::create([
                            'user_id' => Auth::user()->id,
                            'stock_id' => $item->id,
                            'content' => $message
                        ]);
                    }
                }
            }

        }
        return view('sales.summary', compact('stock', 'parts', 'request', 'customersForAutocomplete', 'data', 'errorMessage'));
    }

    public function getSummaryOther(Request $request)
    {
        $validator = new PricesValidator(app('translator'), $request->all());
        $data = $this->validateWithObject($request, $validator);

        $stock = Stock::whereIn('id', array_keys($data['items']))
            ->whereIn('status', Auth::user()->allowed_statuses_buying)
            ->get();
        $amount = 0;

        foreach ($stock as $item) {
            if (isset($data['items'][$item->id]['price']) && $data['items'][$item->id]['price'] != '')
                $amount += $data['items'][$item->id]['price'];
            else
                $amount += $item->sale_price;
        }

        return view('sales.summary-other', compact('stock', 'request', 'amount'));
    }

    public function postSaveOther(Request $request)
    {
        $validator = new SaleValidator(app('translator'), $request->all());
        $data = $this->validateWithObject($request, $validator);

        if (!$request->recycler || $request->recycler == '')
            return back()->with('messages.error', 'Recycler must be set');
        elseif ($request->recycler == 'Other' && $request->other_recycler == '')
            return back()->with('messages.error', 'Other Recycler must be set if Other is selected');
        elseif (!$request->recyclers_order_number || $request->recyclers_order_number == '')
            return back()->with('messages.error', 'Recyclers Order Number must be set');

        if ($request->recycler == 'Other')
            $recycler = $request->other_recycler;
        else
            $recycler = $request->recycler;

        $accountName = '';
        if ($recycler == "Music Magpie") {
            $accountName = $request->account_name;
        }

        $lockKey = substr('sale_' . md5(rand()), 0, 32);
        $ids = array_keys($data['items']);
        SysLog::log("Setting lock_key to \"$lockKey\".", Auth::user()->id, $ids);
        Stock::whereIn('id', $ids)->where('locked_by', '')->update(['locked_by' => $lockKey]);
        $countLocked = Stock::whereIn('id', $ids)->where('locked_by', $lockKey)->count();

        if (count($ids) !== $countLocked) {
            Stock::whereIn('id', $ids)->where('locked_by', $lockKey)->update(['locked_by' => '']);

            if ($countLocked === 0) {
                return redirect('stock')->with(
                    'messages.warning',
                    "While you were creating your sale the requested items have been sold. Sorry for your inconvenience"
                );
            } else {
                return back()->with(
                    'messages.warning',
                    "While you were creating your sale some of the requested items have been sold. Sorry for your " .
                    "inconvenience. Below you can see the items still available."
                );
            }
        }

        $items = Stock::whereIn('id', $ids)->get();
        $sale = new Sale();
        $sale->user_id = Auth::user()->id;
        $sale->created_by = Auth::user()->id;
        $sale->other_recycler = $recycler;
        $sale->invoice_total_amount = $request->amount;
        $sale->recyclers_order_number = $request->recyclers_order_number;
        $sale->invoice_creation_status = Invoice::CREATION_STATUS_ERROR;
        $sale->invoice_status = Invoice::STATUS_OPEN;
        if ($accountName)
            $sale->account_name = $accountName;
        $sale->save();

        foreach ($items as $item) {
            $item->status = Stock::STATUS_SOLD;
            $item->sale_price = $data['items'][$item->id]['price'];
            $item->sold_at = new Carbon;
            $item->sale()->associate($sale);
            $item->sale_history()->sync([$sale->id], false);
            $item->save();
            if ($item->imei) {
                $otherRecycle = new OtherRecycle();
                $otherRecycle->imei = $item->imei;
                $otherRecycle->sold_to = $request->recycler;
                $otherRecycle->save();
            }
        }

        Auth::user()->basket()->sync([]);

        return redirect()->route('sales.single', ['id' => $sale->id]);
    }

    public function postSave(Request $request, Invoicing $invoicing)
    {


        $validator = new SaleValidator(app('translator'), $request->all());
        $data = $this->validateWithObject($request, $validator);

        if ($request->buyers_ref) {


            $buyersRef = Sale::where(['buyers_ref' => trim($request->buyers_ref), 'customer_api_id' => $request->customer_external_id])->first();

            if (!is_null($buyersRef)) {
                session()->put('validation_error', 'sajsakahkj');

                $request->session()->put('pre_data', [
                    'data' => $validator->getData(),
                    'validation_message' => "Buyer Ref has already been taken.",
                ]);


                return back();

            }

        }


        $customerUser = User::where('invoice_api_id', $data['customer_external_id'])->firstOrFail();
        if ($customerUser->suspended) {
            return back()->with('messages.error', 'Customer is suspended');
        }


        $deliveryName = !empty($data['customer_is_collecting']) ? null : $invoicing->getDeliveryForUser($customerUser);


        $discount = 0;
        $discountType = "";
        $grade = "";
        if ($request->voucher) {
            $voucher = Voucher::where('code', $request->voucher)->first();
            if ($voucher) {
                $now = new Carbon;
                if ($voucher->expiration_date > $now) {
                    $discount = $voucher->discount;
                    $discountType = $voucher->type;
                    $grade = $voucher->grade;
                }
            }
        }

        if (!empty($data['customer_modified'])) {
            $customer = $invoicing->getCustomer($data['customer_external_id']);
            $customer->fill($data['customer']);
            $customer->billing_address = new Address($data['customer']['billing_address']);
            $customer->shipping_address = new Address($data['customer']['shipping_address']);
            $invoicing->updateCustomer($customer);
        }

        $partsItemsBasket = Auth::user()->part_basket;
        if (!is_null($partsItemsBasket)) {
            foreach ($partsItemsBasket as $item) {
                $part = Part::where('id', $item->part_id)->first();
                if ($part->quantity < $item->quantity) {
                    return redirect('basket')->with('messages.warning', "While you were creating sale some of the requested parts have been sold. Sorry for your inconvenience");
                }
                if ($part->sale_price <= 0) {
                    return redirect('basket')->with('messages.warning', "Price cannot be null");
                }
            }
        }

        $lockKey = substr('sale_' . md5(rand()), 0, 32);

        if (isset($data['items'])) {
            $ids = array_keys($data['items']);
            SysLog::log("Setting lock_key to \"$lockKey\".", Auth::user()->id, $ids);
            Stock::whereIn('id', $ids)->where('locked_by', '')->update(['locked_by' => $lockKey]);
            $countLocked = Stock::whereIn('id', $ids)->where('locked_by', $lockKey)->count();

            $items = Stock::whereIn('id', $ids)->get();
        }


        $sale = new Sale();
        $sale->user_id = $customerUser->id;
        $sale->created_by = Auth::user()->id;
        $sale->customer_api_id = $data['customer_external_id'];
        $sale->buyers_ref = trim($request->buyers_ref);
        $sale->platform = $request->platform;
        $sale->save();

        // add stock log when create sale
        if (isset($data['items'])) {
            $ids = array_keys($data['items']);
            $items = Stock::whereIn('id', $ids)->get();
            if (count($items) > 0) {
                foreach ($items as $item) {
                    $customerName = $customerUser->first_name . " " . $customerUser->last_name;
                    // $item_price = $data['items'][$item->id]['price'];
                    $item_price = $item->sale_price;
                }
            }
        }

        $partsItems = [];

        if (!is_null($partsItemsBasket)) {
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

        if (isset($data['items'])) {
            if ($discount && $discountType == Voucher::TYPE_FIXED_VALUE) {
                if ($grade) {
                    $itemsMatchingGrade = $items->where('grade', $grade)->count();
                    if ($itemsMatchingGrade > 0) {
                        $discountPerItem = $discount / $itemsMatchingGrade;
                    } else {
                        $discountPerItem = 0;
                    }
                } else {
                    $discountPerItem = $discount / $items->count();
                }
            }
            foreach ($items as $item) {
                // calculate discount
                $item_price = $data['items'][$item->id]['price'];
                if ($discount && $discountType == Voucher::TYPE_PERCENTAGE_VALUE) {
                    if ($grade) {
                        if ($item->grade == $grade)
                            $item_price = $item_price - ($discount / 100 * $item_price);
                    } else {
                        $item_price = $item_price - ($discount / 100 * $item_price);
                    }
                } elseif ($discount && $discountType == Voucher::TYPE_FIXED_VALUE) {
                    if ($grade) {
                        if ($item->grade == $grade) {
                            $item_price = $item_price - $discountPerItem;
                        }
                    } else {
                        $item_price = $item_price - $discountPerItem;
                    }
                }

                if (config('app.env') === 'production' && in_array($item->shown_to, [Stock::SHOWN_TO_EBAY, Stock::SHOWN_TO_EBAY_AND_SHOP])) {
                    artisan_call_background('orderhub:update-single-sku-quantity', $item->sku);
                }

                $item->status = Stock::STATUS_SOLD;
                $item->original_sale_price = $data['items'][$item->id]['price'];
                $item->sale_price = $item_price;
                $item->sold_at = new Carbon;
                $item->sale()->associate($sale);
                $item->sale_history()->sync([$sale->id], false);
                $item->save();
                $content = "This device has been sold for £" . $item_price . " on " . date('m-d-Y') . " to " . $customerName;
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

        //dd($customerUser->quickbooks_customer_category);
        if (!is_null($customerUser->quickbooks_customer_category)) {
            $vatType = $items[0]['vat_type'];
            $customerLocation = $customerUser->location;

            $saleName = getQuickBookServiceProductName($customerUser->quickbooks_customer_category, $vatType, $customerLocation, $request->platform);

        } else {
            $saleName = $invoicing->getSaleForUser($customerUser);
        }



        dispatch( new InvoiceCreate($sale,
            $customerUser,
            $saleName,
            $request->platform,
            $deliveryName));



        //Queue::pushOn('emails', new UnlockEmail($sale));
        return Auth::user()->type === 'user'
            ? view('sales.select-payment-method', compact('sale'))
            : redirect()->route('sales')->with('sales.created_id', $sale->id);
    }

    public function postSummaryBatch(Request $request, Invoicing $invoicing)
    {
        /*$customers = $invoicing->getRegisteredSelectedCustomers();
        if($customers->isEmpty()) {
            $customers = $invoicing->getCustomers()->keyBy('external_id');
            foreach($customers as $customer=>$val) {
                $user = User::where('invoice_api_id', $customer)->first();
                if(!$user) {
                    unset($customers[$customer]);
                }
            }
        }
        $customersForAutocomplete = $customers->map(
            function($a) { return ['label' => $a->full_name, 'value' => $a->external_id]; }
        );*/
        $customersForAutocomplete = [];
        $stock = Stock::whereIn('id', array_keys($request->items))
            ->orderBy('purchase_date', 'desc')
            ->get();
        $batch = $request->batch;
        $price = $request->price;

        $message = "";

        if (Auth::user()->type === 'user') {
            $price = $stock->first()->batch->sale_price;
        }
        if (Auth::user()->type === 'user' && ($price == 0 || count($stock) == 0)) {
            return back()->with('messages.warning', 'Something went wrong');
        }
        return view('sales.summary-batch', compact('stock', 'request', 'customers', 'customersForAutocomplete', 'batch', 'price'));
    }

    public function postSaveBatch(Request $request, Invoicing $invoicing)
    {
        if (Auth::user()->type === 'user') {
            $customerUser = User::where('invoice_api_id', Auth::user()->invoice_api_id)->firstOrFail();
            $deliveryName = $invoicing->getDeliveryForUser($customerUser);
        } else {
            $customerUser = User::where('invoice_api_id', $request->customer_external_id)->firstOrFail();
            $deliveryName = $request->customer_is_collecting ? null : $invoicing->getDeliveryForUser($customerUser);
        }

        if ($customerUser->suspended) {
            return redirect()->route('batches')->with('messages.error', 'Customer is suspended');
        }

        if (count($request->items) > 0) {
            $ids = array_keys($request->items);

            $items = Stock::whereIn('id', $ids)->get();
            if (count($items) > 0) {
                foreach ($items as $item) {
                    $customerName = $customerUser->first_name . " " . $customerUser->last_name;
                    $item_price = $item->sale_price;
                }
            }
        }

        if ($request->customer_modified) {
            $customer = $invoicing->getCustomer($request->customer_external_id);
            $customer->fill($request->customer);
            $customer->billing_address = new Address($request->customer['billing_address']);
            $customer->shipping_address = new Address($request->customer['shipping_address']);
            $invoicing->updateCustomer($customer);
        }

        $lockKey = substr('sale_' . md5(rand()), 0, 32);
        $ids = array_keys($request->items);
        SysLog::log("Setting lock_key to \"$lockKey\".", Auth::user()->id, $ids);
        Stock::whereIn('id', $ids)->update(['locked_by' => $lockKey]);
        $countLocked = Stock::whereIn('id', $ids)->where('locked_by', $lockKey)->count();

        if (count($ids) !== $countLocked) {
            Stock::whereIn('id', $ids)->where('locked_by', $lockKey)->update(['locked_by' => '']);

            if ($countLocked === 0) {
                return redirect('stock')->with(
                    'messages.warning',
                    "While you were creating your sale the requested items have been sold. Sorry for your inconvenience"
                );
            } else {
                return back()->with(
                    'messages.warning',
                    "While you were creating your sale some of the requested items have been sold. Sorry for your " .
                    "inconvenience. Below you can see the items still available."
                );
            }
        }

        $items = Stock::whereIn('id', $ids)->get();

        if (Auth::user()->type === 'user' && $items->first()->batch->sale_price == 0) {
            Stock::whereIn('id', $ids)->where('locked_by', $lockKey)->update(['locked_by' => '']);
            return redirect()->route('batches')->with('messages.warning', 'Something went wrong');
        }

//		return $items;
        $sale = new Sale();
        $sale->user_id = $customerUser->id;
        $sale->created_by = Auth::user()->id;
        $sale->customer_api_id = $customerUser->invoice_api_id;
        $sale->save();
        if (Auth::user()->type === 'user') {
            $price = $items->first()->batch->sale_price;
        } else {
            $price = $request->price;
        }

        if (!$request->auction && $request->price) {
            artisan_call_background('batches:sold-email', [$items->first()->batch_id, $customerUser->id, $request->price]);
        }

        $itemsTotalPrice = $items->sum('sale_price');
        $diff = number_format($price / $itemsTotalPrice, 2);
        foreach ($items as $item) {
            // each item price must be updated
            $item->original_sale_price = $item->sale_price;
            $item_price = $item->sale_price * $diff;
            $item->status = Stock::STATUS_SOLD;
            $item->sale_price = $item_price; //$request->items[$item->id]['price'];
            $item->sold_at = new Carbon;
            $item->sale()->associate($sale);
            $item->sale_history()->sync([$sale->id], false);
            $item->save();
            $content = "This device has been sold for £" . $item_price . " on " . date('m-d-Y') . " to " . $customerName;
            StockLog::create(['stock_id' => $item->id, 'user_id' => Auth::user()->id, 'content' => $content]);
        }
        $batch = $request->batch;

        Auth::user()->basket()->sync([]);
        if (isset($auction)) {
            Queue::pushOn(
                'invoices',
                new InvoiceCreate(
                    $sale,
                    $customerUser,
                    $invoicing->getSaleForUser($customerUser),
                    $deliveryName,
                    $batch,
                    $price,
                    $auction
                )
            );
        } else {
            Queue::pushOn(
                'invoices',
                new InvoiceCreate(
                    $sale,
                    $customerUser,
                    $invoicing->getSaleForUser($customerUser),
                    $deliveryName,
                    $batch,
                    $price
                )
            );
        }
        return Auth::user()->type === 'user'
            ? view('sales.select-payment-method', compact('sale'))
            : redirect()->route('sales')->with('sales.created_id', $sale->id);
    }

    public function postSummaryAuctionBatch(Request $request, Invoicing $invoicing)
    {
        $customers = $invoicing->getRegisteredSelectedCustomers();
        if ($customers->isEmpty()) {
            $customers = $invoicing->getCustomers()->keyBy('external_id');
            foreach ($customers as $customer => $val) {
                $user = User::where('invoice_api_id', $customer)->first();
                if (!$user) {
                    unset($customers[$customer]);
                }
            }
        }
        $customersForAutocomplete = $customers->map(
            function ($a) {
                return ['label' => $a->full_name, 'value' => $a->external_id];
            }
        );
        $auction = Auction::findOrFail($request->auction);
        $batch = $auction->batch_id;
        $stock = Stock::whereIn('id', array_keys($request->items))
            ->get();

        $bid = AuctionBid::query()
            ->where('auction_id', $auction->id)
            ->orderBy('bid', 'desc')
            ->first();
        $price = $bid->bid;
        $user = User::findOrFail($bid->user_id);
        return view('sales.summary-batch', compact('stock', 'request', 'customers', 'customersForAutocomplete', 'batch', 'price', 'user', 'auction'));
    }

    public function getTech360Invoice($id, Invoicing $invoicing)
    {
        $check = DB::table('tech_360_sales')->where('invoice', $id)->first();
        if (!$check) {
            print("Not Found");
            die;
        }
        $invoicePath = $invoicing->getTech360InvoiceDocument($id);
        header('Content-type: application/pdf');
        readfile($invoicePath);
        die;
    }

    public function postOtherChangeRecycler(Request $request)
    {
        $sale = Sale::findOrFail($request->id);
        if ($request->recycler == 'Other' && $request->other_recycler == '')
            return back()->with('messages.error', 'Other Recycler must be set if Other is selected');

        if ($request->recycler == 'Other')
            $recycler = $request->other_recycler;
        else
            $recycler = $request->recycler;

        $sale->other_recycler = $recycler;
        $sale->save();

        return back()->with('messages.success', 'Recycler has been changed.');
    }

    public function postOtherRemoveItem(Request $request)
    {
        $item = Stock::findOrFail($request->stock_id);
        $sale = Sale::findOrFail($request->sale_id);

        if (!$item->sale) {
            return back()->with('messages.error', "Requested device is not sold.");
        }

        if ($item->sale->id != $sale->id) {
            return back()->with('messages.error', "Requested device sale doesn't match sale id");
        }

        DB::table('new_sales_stock')->where('stock_id', $item->id)->where('sale_id', $sale->id)->delete();
        $item->returnToStock();

        $amount = $sale->stock->sum('sale_price');
        $sale->invoice_total_amount = $amount;
        $sale->save();

        return back()->with('messages.success', "Item has been returned to stock");
    }

    public function postOtherChangePrice(Request $request)
    {

        $item = Stock::findOrFail($request->stock_id);
        $sale = Sale::findOrFail($request->sale_id);
        if (!$item->sale) {
            return back()->with('messages.error', "Requested device is not sold.");
        }

        if ($item->sale->id != $sale->id) {
            return back()->with('messages.error', "Requested device sale doesn't match sale id");
        }

        $item->sale_price = $request->sale_price;
        $item->save();

        $amount = $sale->stock->sum('sale_price');
        $sale->invoice_total_amount = $amount;
        $sale->save();

        return back()->with('messages.success', 'Sales Price has been changed.');
    }

    public function getPrintReceipt(Request $request)
    {
        if ($request->ref) {
            $ref = $request->ref;
            if (strtolower(substr($ref, 0, 3)) === 'rct') {
                $ref = substr($ref, 3);
            }
            $item = Stock::where('id', $ref)->first();
            if (!$item) {
                return back()->with('messages.error', 'Item not found.');
            }
            if (!$item->sold) {
                return back()->with('messages.error', "Item has not been sold yet - current status: $item->status");
            }

            $pdf = App::make('dompdf.wrapper');
            $pdf->setPaper(array(0, 0, 203.76, 421.68));
            $pdf->loadView('sales.receipt-pdf', ['item' => $item]);
            return $pdf->stream();
        }
        return view('sales.print-receipt');
    }

    public function getCustomOrder(Request $request)
    {
        $batch = $request->batch ? Batch::findOrFail($request->batch) : null;
        $name = $batch ? $batch->name : null;
        $price = $request->price ?: null;
        $customerId = $request->customer_id ?: null;
        return view('sales.custom-order', compact('batch', 'price', 'name', 'customerId'));
    }

    public function postCustomOrderCreate(Request $request)
    {
        $this->validate($request, [
            'item_name' => 'required',
            'amount' => 'required',
            'customer_external_id' => 'required|min:1',
            'vat_type' => 'required'
        ]);

        $customerUser = User::where('invoice_api_id', $request->customer_external_id)->firstOrFail();

        $batch = null;
        if ($request->batch_id) {
            $batch = Batch::findOrFail($request->batch_id);
        }

        if ($batch && !$batch->sellable) {
            return back()->with('messages.error', 'Unable to sell this batch');
        }

        $sale = new Sale();
        $sale->user_id = $customerUser->id;
        $sale->created_by = Auth::user()->id;
        $sale->customer_api_id = $request->customer_external_id;
        $sale->item_name = $request->item_name;
        $sale->vat_type = $request->vat_type;
        $sale->invoice_total_amount = $request->amount;
        if ($batch && $batch->sellable) {
            $sale->batch_id = $batch->id;
        }
        $sale->save();


        $salename = $batch ? app('App\Contracts\Invoicing')->getSaleForUser($customerUser) :
            Invoicing::SALE_OTHER;


        dispatch(new InvoiceCustomOrderCreate($sale, $customerUser, $salename));


        return redirect()->route('sales')->with('sales.created_id', $sale->id);
    }

    public function postReCreateInvoice(Request $request)
    {
        $sale = Sale::findOrFail($request->id);

        $invoice_details = $sale->invoice_details;

        if (!$invoice_details) {
            return back()->with('messages.error', "No Invoice Details - Unable to Re-Create invoice");
        }

        $customerUser = User::find($invoice_details->customerUser);
        $saleName = $invoice_details->saleName;
        $delivery = $invoice_details->deliveryName;

        $invoicing = app('App\Contracts\Invoicing');
        if ($sale->invoice_api_id)
            $invoicing->voidInvoice($sale);

        switch ($invoice_details->type) {
            case Sale::INVOICE_TYPE_INVOICE_CREATE:
            {
                $batch = $invoice_details->batch;
                $price = $invoice_details->price;
                $auction = $invoice_details->auction ? Auction::find($invoice_details->auction) : null;

                Queue::pushOn('invoices', new InvoiceCreate($sale, $customerUser, $saleName, $delivery, $batch, $price, $auction));
                break;
            }
            case Sale::INVOICE_TYPE_INVOICE_CUSTOM_ORDER_CREATE:
            {
                Queue::pushOn('invoices', new InvoiceCustomOrderCreate($sale, $customerUser, $saleName));
                break;
            }
            /*case Sale::INVOICE_TYPE_INVOICE_EBAY_CREATE: {
                Queue::pushOn('invoices', new InvoiceEbayCreate($sale, $customerUser, $saleName));
                break;
            }
            case Sale::INVOICE_TYPE_INVOICE_EPOS_CREATE: {
                Queue::pushOn('invoices', new InvoiceEposCreate($sale, $customerUser, $saleName));
                break;
            }
            case Sale::INVOICE_TYPE_INVOICE_MIGHTY_DEALS_CREATE: {
                Queue::pushOn('invoices', new InvoiceMightyDealsCreate($sale, $customerUser, $saleName));
                break;
            }
            case Sale::INVOICE_TYPE_INVOICE_ORDERHUB_CREATE: {
                Queue::pushOn('invoices', new InvoiceOrderhubCreate($sale, $customerUser, $saleName));
                break;
            }*/
        }

        sleep(5);

        return back()->with('messages.success', 'Invoice will be re-created');

    }

    public function postRemoveItemFromSale(Request $request)
    {
        $sale = Sale::findOrFail($request->sale_id);
        $stock = Stock::findOrFail($request->stock_id);

        DB::table('new_sales_stock')->where('sale_id', $sale->id)->where('stock_id', $stock->id)->delete();
        $stock->returnToStock();

        return back()->with('messages.success', 'Item has been removed from sale. Please Re-Create the Invoice.');
    }

    public function postChangeItemSalePrice(Request $request)
    {
        $sale = Sale::findOrFail($request->sale_id);
        $stock = Stock::findOrFail($request->stock_id);
        $stock->sale_price = $request->sale_price;

        $totalCosts = $stock->total_cost_with_repair;

        if ($stock->vat_type === "Standard" && $request->sale_price) {

            $calculations = calculationOfProfit($request->sale_price, $totalCosts, $stock->vat_type);

            $stock->sale_vat = $calculations['sale_vat'];
            $stock->total_price_ex_vat = $calculations['total_price_ex_vat'];
            $stock->profit = $calculations['profit'];
            $stock->true_profit = $calculations['true_profit'];
            $stock->marg_vat = $calculations['marg_vat'];


        } else if ($request->sale_price) {
            $calculations = calculationOfProfit($request->sale_price, $totalCosts, $stock->vat_type, $stock->purchase_price);
            $stock->marg_vat = $calculations['marg_vat'];
            $stock->profit = $calculations['profit'];
            $stock->true_profit = $calculations['true_profit'];
            $stock->sale_vat = $calculations['sale_vat'];
            $stock->total_price_ex_vat = $calculations['total_price_ex_vat'];

        }


        if ($stock->getOriginal('sale_price') != $stock->sale_price) {
            StockLog::create([
                'stock_id' => $stock->id,
                'user_id' => Auth::user()->id,
                'content' => 'Changed Sale Price from ' . $stock->getOriginal('sale_price') . ' to ' . $stock->sale_price
            ]);
        }
        $stock->save();


        return back()->with('messages.success', 'Item Sales Price has been updated. Please Re-Create the Invoice');
    }

    public function postMultipleChangeItemSalePrice(Request $request)
    {
        $StockIdAndSalePrice = array_combine($request->stock_id, $request->sale_price);

        foreach ($StockIdAndSalePrice as $key => $data) {
            if ($data !== "") {
                $stock = Stock::findOrFail($key);
                $stock->sale_price = $data;

                if ($stock->getOriginal('sale_price') != $stock->sale_price) {
                    StockLog::create([
                        'stock_id' => $stock->id,
                        'user_id' => Auth::user()->id,
                        'content' => 'Changed Sale Price from ' . $stock->getOriginal('sale_price') . ' to ' . $stock->sale_price
                    ]);
                }
                $stock->save();
            }
        }

        return back()->with('messages.success', 'Item Sales Price has been updated. Please Re-Create the Invoice');
    }


    public function postBulkUpdateSalePrice(Request $request)
    {
        $sale = Sale::findOrFail($request->id);

        $items = Stock::where('sale_id', $sale->id)->update(['sale_price' => $request->sale_price]);

        return back()->with('messages.success', "$items Items Sales Price has been updated. Please Re-Create the Invoice");
    }

    public function postCheckAllNetworks(Request $request)
    {
        $sale = Sale::findOrFail($request->id);

        $i = 0;
        foreach ($sale->stock as $item) {
            $gsxCheck = new App\Mobicode\GsxCheck();
            $gsxCheck->user_id = Auth::user()->id;
            $gsxCheck->status = App\Mobicode\GsxCheck::STATUS_NEW;
            $gsxCheck->imei = $item->imei;
            $gsxCheck->sale_id = $sale->id;
            $gsxCheck->stock_id = $item->id;
            $gsxCheck->service_id = 118;
            $gsxCheck->save();
            $i++;
        }

        return back()->with('messages.success', "Network Checks created for $i items");
    }

    public function postUpdateTracking(Request $request)
    {
        $sale = Sale::findOrFail($request->id);
        $sale->tracking_number = $request->tracking_number;
        $sale->courier = $request->courier;
        $sale->save();

        Queue::pushOn('emails', new TrackingUpdated($sale));

        return back()->with('messages.success', 'Tracking Updated - Email and SMS will be sent');

    }

    public function getSalesAccessories(Request $request)
    {
        $salesAccessories = Accessories::orderBy('id', 'asc');

        if ($request->term) {
            $salesAccessories->where(function ($q) use ($request) {
                $q->where('name', 'like', "%$request->term%");
                $q->orWhere('sku', 'like', "%$request->term%");
            });
        }

        $salesAccessories = $salesAccessories->paginate(config('app.pagination'));

        if ($request->ajax()) {
            return response()->json([
                'itemsHtml' => View::make('sales.accessories-list', compact('salesAccessories'))->render(),
                'paginationHtml' => $salesAccessories->appends($request->all())->render()
            ]);
        }

        return view('sales.accessories', compact('salesAccessories'));
    }

    public function postSalesAccessoriesCreate(Request $request)
    {
        $accessory = new Accessories();
        $accessory->fill($request->all());

        if ($request->hasFile('image')) {

            $file = $request->file('image');

            $dir = base_path('public/img/accessories/');
            $filename = rand() . '.' . $file->getClientOriginalExtension();
            Image::make($file)->resize(2048, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            })->save($dir . $filename, 80);
            $filename = asset('img/accessories/' . $filename);
            $accessory->image = $filename;
        }

        $accessory->save();

        return redirect()->route('sales.accessories');
    }

    public function getSalesAccessoriesSingle($id)
    {
        $accessory = Accessories::findOrFail($id);

        // return view('buyback.single-buyback', compact('accessory'));
        return view('sales.single-accessories', compact('accessory'));
    }

    public function postSalesAccessoriesUpdate(Request $request)
    {
        $accessory = Accessories::findOrFail($request->id);

        $accessory->fill($request->all());


        if ($request->hasFile('image')) {

            $file = $request->file('image');

            $dir = base_path('public/img/accessories/');
            $filename = rand() . '.' . $file->getClientOriginalExtension();
            Image::make($file)->resize(2048, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            })->save($dir . $filename, 80);
            $filename = asset('img/accessories/' . $filename);

            // delete old image
            if ($request->old_image) {
                $explode = explode('/accessories/', $request->old_image);
                if (!empty($explode[1])) {
                    unlink('img/accessories/' . $explode[1]);
                }
            }

            $accessory->image = $filename;

        }

        $accessory->save();

        return back()->with('messages.success', 'Record has been updated.');
    }

    public function updatePrice(Request $request)
    {
        $data = array_combine($request->ids, $request->value);
        $vatType = array_combine($request->ids, $request->vat_type);

        if (count(array_unique($vatType)) > 1) {
            return ['error' => 'true', 'message' => 'You cannot add items with different VAT Type to the same sale'];
        }

        if (count($data) > 0) {
            foreach ($data as $key => $value) {

                $stock = Stock::find($key);
                if ($vatType[$request->ids[0]] === "Standard" && $value) {
                    $totalCosts = $stock->total_cost_with_repair;
                    $calculations = calculationOfProfit($value, $totalCosts, $vatType[$request->ids[0]]);
                    $stock->sale_price = $value;
                    $stock->sale_vat = $calculations['sale_vat'];
                    $stock->total_price_ex_vat = $calculations['total_price_ex_vat'];
                    $stock->profit = $calculations['profit'];
                    $stock->true_profit = $calculations['true_profit'];

                    foreach ($vatType as $id => $vat) {

                        if ($id === $key) {

                            $stock->vat_type = $vat;
                        }
                    }
                    $stock->marg_vat = null;


                } else if ($value) {
                    $totalCosts = $stock->total_cost_with_repair;
                    $calculations = calculationOfProfit($value, $totalCosts, $vatType[$request->ids[0]], $stock->purchase_price);
                    $stock->sale_price = $value;
                    $stock->profit = $calculations['profit'];
                    $stock->marg_vat = $calculations['marg_vat'];
                    $stock->true_profit = $calculations['true_profit'];
                    $stock->sale_vat = $calculations['sale_vat'];
                    foreach ($vatType as $id => $vat) {

                        if ($id === $key) {
                            $stock->vat_type = $vat;
                        }
                    }
                    $stock->total_price_ex_vat = $calculations['total_price_ex_vat'];
                }
                $stock->save();
            }
        }
    }

    public function updateShippingCost(Request $request)
    {
        $newSales = Sale::find($request->sale_id);
        if (!is_null($request->platform_fee)) {

            $newSales->platform_fee = $request->platform_fee;
        }
        if (!is_null($request->shipping_cost)) {
            $newSales->shipping_cost = $request->shipping_cost;
        }
        $newSales->save();

        return back()->with("messages.success", "Update Shipping Cost");
    }

    public function getDashboard(Request $request)
    {
        $salesQuery = Sale::whereNotIn('invoice_status', ['voided'])->whereNotIn('invoice_creation_status', ['error', 'not initialised']);
        $counting = [];
        $stockList = [];
        $field = [];
        $monthList = [
            '1' => 'January',
            '2' => 'February',
            '3' => 'March',
            '4' => 'April',
            '5' => 'May',
            '6' => 'June',
            '7' => 'July',
            '8' => 'August',
            '9' => 'September',
            '10' => 'October',
            '11' => 'November',
            '12' => 'December',
        ];


        if (!count($request->all())) {
            //  $salesQuery->whereRaw('Date(created_at) = CURDATE()');
            $now = Carbon::now();
            $salesQuery->whereRaw('MONTH(created_at) =' . $now->month)->whereRaw('YEAR(created_at) =' . $now->year);
            $field['Current Month'] = Carbon::now()->format('F');
        }
        if ($request->platform) {

            $salesQuery->where('platform', $request->platform);
            $field['Platform'] = $request->platform;
        }
        if ($request->vat_type) {
            $salesQuery->whereHas('stock', function ($q) use ($request) {
                $q->where('vat_type', $request->vat_type);
            });

            $field['Vat Type'] = $request->vat_type;
        }
        if ($request->category) {
            $salesQuery->whereHas('stock', function ($q) use ($request) {
                $q->where('product_type', $request->category);
            });
            $field['Product Type'] = $request->category;
        }
        if ($request->month) {
            $month = '';
            foreach ($monthList as $key => $value) {
                if ($key == $request->month) {
                    $month = $value;
                }
            }
            $salesQuery->whereRaw('MONTH(created_at) = ' . $request->month);
            $field['Month'] = $month;
        }
        if ($request->year) {
            $salesQuery->whereRaw('YEAR(created_at) = ' . $request->year);
            $field['Year'] = $request->year;
        }
        if ($request->days) {
            if ($request->days === "current") {
                $salesQuery->whereRaw('Date(created_at) = CURDATE()');
                $field['Current Day'] = Carbon::now()->format('d M Y ');
            } elseif ($request->days === "month") {

                $now = Carbon::now();
                $salesQuery->whereRaw('MONTH(created_at) =' . $now->month)->whereRaw('YEAR(created_at) =' . $now->year);
                $field['Current Month'] = Carbon::now()->format('F');
            } elseif ($request->days === "week") {
                $salesQuery->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);

                $field['Current Week'] = Carbon::now()->startOfWeek()->format('D') . " To " . Carbon::now()->endOfWeek()->format("D");
            }
        }
        if ($request->month) {
            $salesQuery->whereRaw('MONTH(created_at) = ' . $request->month);
        }
        if ($request->start || $request->end) {
            $salesQuery->whereBetween('created_at', [$request->start, $request->end]);
            $field['Date Between'] = $request->start . " To " . $request->end;
        }
        $numberOfItem = $salesQuery->count();
        $salesQuery = $salesQuery->groupBy(DB::raw("DATE_FORMAT(created_at, '%d-%m-%Y')"))->orderBy('created_at', 'DESC')->get();
        $trueProfit = 0;
        $sales_total_rev = 0;
        $estNetProfitPer = 0;
        $soldItems = 0;
        $totalVatMargin = 0;
        $final = 0;
        $supplierPre = '';
        // $estProfitSPModel=0;
        $finalTotalProfitSPModel = 0;
        $finalTotalSPItems = 0;
        $finalTotalSPNonItems = 0;
        $finalTotalProfitNonSPModel = 0;
        $finalTotalEstNetProfit = 0;
        $finalTotalProfitNetPre = 0;
        $totalVatMarginArray = [];
        $finalTotalExVat = 0;

        foreach ($salesQuery as $sale) {
            $date = Carbon::parse($sale->created_at)->format('Y-m-d');
            $rev = 0;
            $trueP = 0;
            $estNetProfit = 0;
            $numberOfSold = 0;
            $totalItemsSoldPS = 0;
            $totalItemsSoldNonPS = 0;
            $totalProfitNonModel = 0;
            $vatType = '';
            $finalTotalSPModel = 0;
            $finalTotalPr = 0;
            $tempTotalEstNetProfit = 0;
            $salesData = Sale::with('stock')->where('created_at', 'like', "%" . $date . "%")->where('invoice_status', '!=', 'voided');
            if ($request->platform) {
                $salesData->where('platform', $request->platform);
            }
            if ($request->vat_type) {
                $salesData->whereHas('stock', function ($q) use ($request) {
                    $q->where('vat_type', $request->vat_type);
                });
            }
            if ($request->category) {
                $salesData->whereHas('stock', function ($q) use ($request) {
                    $q->where('product_type', $request->category);
                });
            }

            $salesData = $salesData->get();


            foreach ($salesData as $data) {

                if (count($data->stock)) {
                    $totalCosts = 0;
                    $estProfitSPModel = 0;
                    $estProfitNonSPModel = 0;
                    $totalExVat = 0;
                    $psTotalSalePrice = 0;
                    $psTotalVat = 0;
                    $totalProfitSPModel = 0;
                    $finalTotalSPNonModel = 0;
                    $totalCostsArray = [];
                    $temItemsSoldNonPSArray = [];
                    $stockMuFlag = false;
                    $totalVatMargin = 0;
                    //    $totalProfitNonModel=0;


                    $temEstNet = 0;
                    $dTotalEstProfit = 0;
                    $dTotalEstProfitPre = 0;
                    $temEstNetAQ = 0;

                    if (count($data->ebay_orders) > 0) {
                        foreach ($data->ebay_orders as $ebay) {
                            foreach ($ebay->EbayOrderItems as $item) {
                                if ($item->quantity > 1) {
                                    if (!is_null(json_decode($item->stock_id))) {

                                        $totalVatMargin = 0;
                                        $vatStockType = '';
                                        $temItemsSoldPs = 0;
                                        $temItemsSoldNonPS = 0;
                                        $vatType = '';
                                        $temEstNet = 0;
                                        $dTotalEstProfitPre = 0;
                                        $dTotalEstProfit = 0;
                                        $stockMuFlag = false;
                                        foreach (json_decode($item->stock_id) as $stockId) {

                                            if ($item->tax_percentage * 100 > 0 || !$item->tax_percentage * 100 && getStockDetatils($stockId)->vat_type === "Standard") {
                                                $vatType = Stock::VAT_TYPE_STD;
                                            } else {
                                                $vatType = Stock::VAT_TYPE_MAG;
                                            }

                                            if (getStockDetatils($stockId)->vat_type === Stock::VAT_TYPE_MAG) {
                                                $totalVatMargin += (getStockDetatils($stockId)->sale_price - getStockDetatils($stockId)->purchase_price) * 16.67 / 100;
                                            }
                                            if (in_array(getStockDetatils($stockId)->status, [Stock::STATUS_SOLD, Stock::STATUS_PAID])) {
                                                $soldItems++;
                                                $numberOfSold++;
                                            }
                                            $totalCosts += getStockDetatils($stockId)->total_cost_with_repair;
                                            $vatStockType = getStockDetatils($stockId)->vat_type;
                                            if ($vatType === Stock::VAT_TYPE_STD) {
                                                $totalExVat += getStockDetatils($stockId)->total_price_ex_vat;
                                            } else {
                                                $psTotalSalePrice += getStockDetatils($stockId)->sale_price;
                                                $psTotalVat += getStockDetatils($stockId)->marg_vat;
                                            }

                                            if (getStockDetatils($stockId)->ps_model) {

                                                if (!is_null(getStockDetatils($stockId)->supplier_id)) {
                                                    if (!is_null(getStockDetatils($stockId)->supplier->recomm_ps)) {
                                                        $totalItemsSoldPS++;
                                                        $temItemsSoldPs++;
                                                        $supplierPre = getStockDetatils($stockId)->supplier->recomm_ps;
                                                        if (getStockDetatils($stockId)->vat_type === Stock::VAT_TYPE_STD) {
                                                            $est = (getStockDetatils($stockId)->total_price_ex_vat * getStockDetatils($stockId)->supplier->recomm_ps) / 100;
                                                            $estProfitSPModel += $est;
                                                        } else {
                                                            $salePrice = getStockDetatils($stockId)->sale_price - getStockDetatils($stockId)->marg_vat;
                                                            $estM = ($salePrice * getStockDetatils($stockId)->supplier->recomm_ps) / 100;
                                                            $estProfitSPModel += $estM;
                                                        }

                                                    }
                                                }
                                            } else {
                                                $totalItemsSoldNonPS++;
                                                $temItemsSoldNonPS++;
                                                $estProfitNonSPModel += getStockDetatils($stockId)->true_profit - $data->platform_fee;
                                            }
                                        }
                                        if ($vatType === "Standard") {
                                            $temEstNet += ($data->invoice_total_amount / 1.2) - $totalCosts - $data->platform_fee - $data->shipping_cost;
                                        } else {

                                            $temEstNet += ($data->invoice_total_amount - ($data->delivery_charges * 20 / 100)) - $totalCosts - $totalVatMargin - $data->platform_fee - $data->shipping_cost;

                                        }
                                        if (abs($estProfitSPModel) > 0) {
                                            if ($temItemsSoldPs && !$temItemsSoldNonPS) {
                                                $totalProfitSPModel += ($temEstNet * $supplierPre) / 100;
                                            } else {
                                                $totalProfitSPModel += ($estProfitSPModel + $sale->delivery_charges) - $sale->shipping_cost;
                                            }
                                        }

                                        if (abs($estProfitSPModel) > 0) {
                                            if (abs($estProfitNonSPModel) > 0) {
                                                $totalProfitNonModel += $estProfitNonSPModel + $sale->delivery_charges;
                                            }
                                        } else {
                                            if (abs($estProfitNonSPModel) > 0) {
                                                $totalProfitNonModel += $temEstNet;
                                            }
                                        }
                                        if ($temItemsSoldPs && !$temItemsSoldNonPS) {
                                            $dTotalEstProfit += ($temEstNet * $supplierPre) / 100;

                                        } elseif (abs($estProfitSPModel) > 0) {
                                            $dTotalEstProfit += $totalProfitSPModel + $totalProfitNonModel;
                                        } else {
                                            $dTotalEstProfit += $temEstNet;

                                        }
                                        if ($vatType === "Standard") {
                                            $dTotalEstProfitPre += $totalExVat > 0 ? ($dTotalEstProfit / $totalExVat) * 100 : 0;
                                        } else {
                                            $totalVatAndSalePrice = $psTotalSalePrice - $psTotalVat;
                                            $dTotalEstProfitPre += $totalVatAndSalePrice > 0 ? ($dTotalEstProfit / $totalVatAndSalePrice) * 100 : 0;

                                        }

                                    }
                                } else {

                                    //$totalVatMargin = 0;
                                    $temItemsSoldPs = 0;
                                    $temItemsSoldNonPS = 0;
                                    $vatType = '';
                                    $temEstNet = 0;
                                    $dTotalEstProfitPre = 0;
                                    $psTotalSalePrice = 0;
                                    $psTotalVat = 0;
                                    $dTotalEstProfit = 0;


                                    $sGStock = Stock::where("id", $item->stock_id)->get();

                                    foreach ($sGStock as $stock) {

                                        $totalCosts += $stock->total_cost_with_repair;
                                        $vatType = $stock->vat_type;

                                        if (in_array($stock->status, [Stock::STATUS_SOLD, Stock::STATUS_PAID])) {
                                            $soldItems++;
                                            $numberOfSold++;
                                        }
                                        if ($vatType === "Margin") {
                                            $totalVatMargin += ($stock->sale_price - $stock->purchase_price) * 16.67 / 100;
                                        }
                                        if ($vatType === Stock::VAT_TYPE_STD) {
                                            $totalExVat += $stock->total_price_ex_vat;

                                        } else {
                                            $psTotalSalePrice += $stock->sale_price;
                                            $psTotalVat += $stock->marg_vat;
                                        }
                                        if ($stock->ps_model) {
                                            if (!is_null($stock->supplier_id)) {
                                                if (!is_null($stock->supplier->recomm_ps)) {
                                                    $totalItemsSoldPS++;
                                                    $temItemsSoldPs++;
                                                    $supplierPre = $stock->supplier->recomm_ps;


                                                    if ($stock->vat_type === Stock::VAT_TYPE_STD) {
                                                        $est = ($stock->total_price_ex_vat * $stock->supplier->recomm_ps) / 100;
                                                        $estProfitSPModel += $est;
                                                    } else {
                                                        $salePrice = $stock->sale_price - $stock->marg_vat;
                                                        $estM = ($salePrice * $stock->supplier->recomm_ps) / 100;
                                                        $estProfitSPModel += $estM;
                                                    }
                                                }
                                            }
                                        } else {
                                            $totalItemsSoldNonPS++;
                                            $temItemsSoldNonPS++;
                                            $estProfitNonSPModel += $stock->true_profit - $data->platform_fee;
                                        }

                                        $stockMuFlag = true;

                                    }


                                }

                            }
                        }
                    } else {
                        $totalVatMargin = 0;
                        $temItemsSoldPs = 0;
                        $temItemsSoldNonPS = 0;
                        $vatType = '';
                        $dTotalEstProfitPre = 0;
                        $dTotalEstProfit = 0;
                        $totalExVat = 0;


                        foreach ($data->stock()->get() as $stock) {

                            $totalCosts += $stock->total_cost_with_repair;
                            $vatType = $data->stock[0]->vat_type;

                            if (in_array($stock->status, [Stock::STATUS_SOLD, Stock::STATUS_PAID])) {
                                $soldItems++;
                                $numberOfSold++;
                            }
                            if ($vatType === "Margin") {
                                $totalVatMargin += ($stock->sale_price - $stock->purchase_price) * 16.67 / 100;
                            }
                            if ($vatType === Stock::VAT_TYPE_STD) {
                                $totalExVat += $stock->total_price_ex_vat;

                            } else {
                                $psTotalSalePrice += $stock->sale_price;
                                $psTotalVat += $stock->marg_vat;
                            }
                            if ($stock->ps_model) {
                                if (!is_null($stock->supplier_id)) {
                                    if (!is_null($stock->supplier->recomm_ps)) {
                                        $totalItemsSoldPS++;
                                        $temItemsSoldPs++;
                                        $supplierPre = $stock->supplier->recomm_ps;


                                        if ($stock->vat_type === Stock::VAT_TYPE_STD) {
                                            $est = ($stock->total_price_ex_vat * $stock->supplier->recomm_ps) / 100;
                                            $estProfitSPModel += $est;
                                        } else {
                                            $salePrice = $stock->sale_price - $stock->marg_vat;
                                            $estM = ($salePrice * $stock->supplier->recomm_ps) / 100;
                                            $estProfitSPModel += $estM;
                                        }
                                    }
                                }
                            } else {
                                $totalItemsSoldNonPS++;
                                $temItemsSoldNonPS++;
                                $estProfitNonSPModel += $stock->true_profit - $data->platform_fee;
                            }

                        }
                        if ($vatType === "Standard") {
                            $temEstNet += ($data->invoice_total_amount / 1.2) - $totalCosts - $data->platform_fee - $data->shipping_cost;
                        } else {
                            $temEstNet += ($data->invoice_total_amount - ($data->delivery_charges * 20 / 100)) - $totalCosts - $totalVatMargin - $data->platform_fee - $data->shipping_cost;

                        }


                        if ($estProfitSPModel > 0) {
                            if ($temItemsSoldPs && !$temItemsSoldNonPS) {
                                $totalProfitSPModel += ($temEstNet * $supplierPre) / 100;
                            } else {
                                $totalProfitSPModel += ($estProfitSPModel + $sale->delivery_charges) - $sale->shipping_cost;
                            }
                        }

                        if (abs($estProfitSPModel) > 0) {
                            if (abs($estProfitNonSPModel) > 0) {
                                $totalProfitNonModel += $estProfitNonSPModel + $sale->delivery_charges;
                            }
                        } else {
                            $totalProfitNonModel += $temEstNet;
                        }

                        if ($temItemsSoldPs && !$temItemsSoldNonPS) {
                            $dTotalEstProfit += ($temEstNet * $supplierPre) / 100;
                        } elseif (abs($estProfitSPModel) > 0) {
                            $dTotalEstProfit += $totalProfitSPModel + $totalProfitNonModel;
                        } else {
                            $dTotalEstProfit += $temEstNet;
                        }
                        if ($vatType === "Standard") {
                            $dTotalEstProfitPre += $totalExVat > 0 ? ($dTotalEstProfit / $totalExVat) * 100 : 0;
                        } else {
                            $totalVatAndSalePrice = $psTotalSalePrice - $psTotalVat;
                            $dTotalEstProfitPre += $totalVatAndSalePrice > 0 ? ($dTotalEstProfit / $totalVatAndSalePrice) * 100 : 0;
                        }

                    }

                    if ($stockMuFlag) {
                        if ($vatType === "Standard") {
                            $temEstNet += ($data->invoice_total_amount / 1.2) - $totalCosts - $data->platform_fee - $data->shipping_cost;

                        } else {
                            $temEstNet += ($data->invoice_total_amount - ($data->delivery_charges * 20 / 100)) - $totalCosts - $totalVatMargin - $data->platform_fee - $data->shipping_cost;
                        }


                        if ($estProfitSPModel > 0) {
                            if ($temItemsSoldPs && !count($temItemsSoldNonPSArray)) {
                                $totalProfitSPModel += ($temEstNet * $supplierPre) / 100;
                            } else {
                                $totalProfitSPModel += ($estProfitSPModel + $sale->delivery_charges) - $sale->shipping_cost;
                            }
                        }


                        if (abs($estProfitSPModel) > 0) {
                            if (abs($estProfitNonSPModel) > 0) {
                                $totalProfitNonModel += $estProfitNonSPModel + $sale->delivery_charges;
                            }
                        } else {

                            if (abs($estProfitNonSPModel) > 0) {
                                $totalProfitNonModel += $temEstNet;
                            }

                        }

                        if ($temItemsSoldPs && !$temItemsSoldNonPS) {
                            $dTotalEstProfit += ($temEstNet * $supplierPre) / 100;
                        } elseif (abs($estProfitSPModel) > 0) {
                            $dTotalEstProfit += $totalProfitSPModel + $totalProfitNonModel;
                        } else {
                            $dTotalEstProfit += $temEstNet;
                        }
                        if ($vatType === "Standard") {
                            $dTotalEstProfitPre += $totalExVat > 0 ? ($dTotalEstProfit / $totalExVat) * 100 : 0;
                        } else {
                            $totalVatAndSalePrice = $psTotalSalePrice - $psTotalVat;
                            $dTotalEstProfitPre += $totalVatAndSalePrice > 0 ? ($dTotalEstProfit / $totalVatAndSalePrice) * 100 : 0;
                        }

                    }

                    if ($vatType === "Standard") {
                        $trueP += ($data->invoice_total_amount / 1.2) - $totalCosts;
                        $trueProfit += ($data->invoice_total_amount / 1.2) - $totalCosts;
                        $rev += $data->invoice_total_amount / 1.2;
                        $sales_total_rev += $data->invoice_total_amount / 1.2;
                        $estNetProfit += ($data->invoice_total_amount / 1.2) - $totalCosts - $data->platform_fee - $data->shipping_cost;
                        //  $estNetProfit+=$temEstNet;
                    } else {
                        $rev += $data->invoice_total_amount - ($data->delivery_charges * 20 / 100);
                        $sales_total_rev += $data->invoice_total_amount - ($data->delivery_charges * 20 / 100);
                        $trueP += ($data->invoice_total_amount - ($data->delivery_charges * 20 / 100)) - $totalCosts - $totalVatMargin;
                        $trueProfit += ($data->invoice_total_amount - ($data->delivery_charges * 20 / 100)) - $totalCosts - $totalVatMargin;
                        $estNetProfit += ($data->invoice_total_amount - ($data->delivery_charges * 20 / 100)) - $totalCosts - $totalVatMargin - $data->platform_fee - $data->shipping_cost;
                        //   $estNetProfit+=$temEstNet;


                    }

                    $finalTotalSPModel += $totalProfitSPModel;


                    $finalTotalSPNonModel += $totalProfitNonModel;
                    $tempTotalEstNetProfit += $dTotalEstProfit;
                    $finalTotalPr += $dTotalEstProfitPre;
                }

            }


            $stockList[] = [
                'day' => $date,
                'rev' => $rev,
                'true_profit' => $trueP,
                'true_profit_pe' => $rev > 0 ? number_format(($trueP / $rev) * 100, 2) . "%" : '0%',
                'est_net_profit' => $estNetProfit,
                'est_net_profit_pe' => $rev > 0 ? number_format(($estNetProfit / $rev) * 100, 2) . "%" : "0%",
                'count' => $salesData->count(),
                'number_of_sold' => $numberOfSold,
                'est_profit_sp_model' => $finalTotalSPModel,
                'est_profit_sp_non_model' => $finalTotalSPNonModel,
                'total_items_sold_non_ps' => $totalItemsSoldNonPS,
                'total_items_sold_ps' => $totalItemsSoldPS,
                // 'total_est_net_profit'=>$tempTotalEstNetProfit,
                'total_est_net_profit' => $finalTotalSPNonModel > 0 && $totalItemsSoldNonPS > 0 ? $finalTotalSPModel + $finalTotalSPNonModel : $tempTotalEstNetProfit,
                'total_est_net_pre' => $finalTotalPr,
                'total_ex_vat' => $totalExVat,

            ];

            $final += $estNetProfit;
            $finalTotalEstNetProfit += $tempTotalEstNetProfit;
            $finalTotalProfitSPModel += $finalTotalSPModel;
            $finalTotalSPItems += $totalItemsSoldPS;
            $finalTotalSPNonItems += $totalItemsSoldNonPS;
            $finalTotalProfitNonSPModel += $totalProfitNonModel;
            $finalTotalProfitNetPre += $finalTotalPr;
            $finalTotalExVat += $totalExVat;
        }

        $total = 0;
        foreach ($stockList as $ty) {

            if (abs($ty['est_profit_sp_model']) > 0 && abs($ty['est_profit_sp_non_model']) > 0) {
                $total += $ty['est_profit_sp_non_model'] + $ty['est_profit_sp_model'];
            } else {
                $total += $ty['total_est_net_profit'];
            }

        }
        $customerReturnItem = CustomerReturns::sum('items_credited');
        $customerReturnValue = CustomerReturns::sum('value_of_credited');
        $customerReturnProfitLost = CustomerReturns::sum('profile_lost');
        $counting[] = [
            'total_true_profit' => $trueProfit,
            'total_res' => $sales_total_rev,
            'number_of_items' => $numberOfItem,
            'sold_item' => $soldItems,
            'est_net_profit' => $final,
            'est_net_profit_pre' => $estNetProfitPer,
            'items_credited' => $customerReturnItem,
            'value_of_credited' => $customerReturnValue,
            'profit_lost_from_customer' => $customerReturnProfitLost,
            'total_sp_model' => $finalTotalProfitSPModel,
            'total_sp_items' => $finalTotalSPItems,
            'total_sp_non_items' => $finalTotalSPNonItems,
            'total_non_model' => $finalTotalProfitNonSPModel,
            'total_est_net_profit' => $total,
            'total_est_profit_pre' => $finalTotalProfitNetPre,
            'total_ex_vat' => $finalTotalExVat,
        ];

        if ($request->ajax()) {

            return [
                'itemsHtml' => view('sales.counting', compact('counting', 'stockList', 'field'))->render(),
            ];
        }
        return view('sales.dashboard', compact('counting', 'stockList', 'field'));
    }

    public function exportCsv(Request $request)
    {

        ini_set('max_execution_time', 30000);
        ini_set("memory_limit", "2048M");

        $stock = Sale::orderBy('id', 'desc')->limit(200);

        if ($request->start_date && $request->last_date) {

            $stock->whereNotIn('invoice_status', ['voided']);
            $stock->whereBetween('created_at', [$request->start_date, $request->last_date]);
        }
        if ($request->customer_id) {
            $stock->where('customer_api_id', $request->customer_id);
        }

        if ($request->status !== "") {
            if ($request->status == 'open_paid_other_recycler' || !$request->status) {
                $stock->whereIn('invoice_status', ['open', 'paid'])->orWhere(function ($q) {
                    return $q->whereNotNull('other_recycler')->whereIn('invoice_status', ['open', 'paid']);
                })->orderBy('id', 'desc');
            } elseif ($request->status == 'paid')
                $stock->whereIn('invoice_status', ['paid'])->whereNull('other_recycler')->orderBy('id', 'desc');
            elseif ($request->status == 'open_paid' || !$request->status)
                $stock->whereIn('invoice_status', ['open', 'paid'])->whereNull('other_recycler')->orderBy('id', 'desc');
            elseif ($request->status == 'any')
                if (Auth::user()->type === 'user')
                    $stock->whereNotIn('invoice_status', ['voided'])->orderBy('id', 'desc');
                else
                    $stock->orderBy('id', 'desc');
            elseif ($request->status == 'other_recycler')
                $stock->whereNotNull('other_recycler')->orderBy('id', 'desc');

        }

        $fields = [
            'Customer' => 'customer',
            'Buyers Ref' => 'buyers_ref',
            'Ship to' => 'ship_to',
            'Date' => 'created_at',
            'Item count' => 'item_count',
            'Items' => 'item_name',
            'Sale VAT Type' => 'vat_type',
            'Sale Total incCarriag' => 'sale_carriage',
            'Sale Total ex Vat incCarriage' => 'sale_total_ex_vat',
            'Total Purchase Cost' => 'total_purchase_cost',
            'Profit' => 'profit',
            'Profit%' => 'profit_per',
            'Marg VAT' => 'margin_vat',
            'True Profit' => 'true_profit',
            'Platform Name' => 'platform',
            'True Profit %' => 'true_profit_pre',
            'Status' => 'invoice_status',
            'Invoice' => 'invoice',
            'Seller Fees + Accessories Cost' => 'platform_fee',
            'Est Shipping Cost' => 'shipping_cost',
            'Est Net Profit' => 'est_net_profit',
//            'Est Net Profit %'=>'est_net_profit_pre',
            'Est Net Profit (Non P/S)' => 'est_net_profit_non_ps',
            'Recomm P/S' => 'est_net_profit_ps',
            'Items Sold Non P/S' => 'items_sold_non_ps',
            'Items Sold P/S' => 'items_sold_ps',
            'Total Est Net Profit' => 'total_est_net_profit',
            'Net Profit%' => 'net_profit_pre'

        ];


        $csvPath = tempnam('/tmp', 'stock-full-export-');
        unlink($csvPath);
        $csvPath .= '.csv';
        $fh = fopen($csvPath, 'w');
        fputcsv($fh, array_keys($fields));

        //dd($stock);
        $stock->chunk(500, function ($items) use ($fields, $fh) {

            foreach ($items as $item) {
                $shipTo = '-';
                $postCode = '';
                $totalExVat = 0;
                $totalPurchasePrice = 0;
                $totalProfit = 0;
                $totalTrueProfit = 0;
                $totalSalePrice = 0;
                $totalVatMargin = 0;
                $totalPurchaseCost = 0;
                $purchasePrice = 0;
                $totalSalePriceDelivery = 0;
                $totalUnlockCost = 0;
                $totalPartCost = 0;
                $totalRepairCost = 0;
                $vatType = '';
                $vatTypeList = [];
                $saleTotalIncCarriage = 0;
                $vatMrg = 0;
                $totalItemsSoldPS = 0;
                $estProfitSPModel = 0;
                $totalItemsSoldNonPS = 0;
                $estProfitNonSPModel = 0;
                $totalNonPsModel = 0;
                $totalPsModel = 0;
                $PStotalExVat = 0;
                $PSTotalSalePrice = 0;
                $PSTotalVatMargin = 0;
                $finaNetProfit = 0;
                $ftProfit = 0;
                $ftTrueProfit = 0;
                $pVatMargin = 0;


                if ($item->customer_api_id) {
                    $customerUser = User::where('invoice_api_id', $item->customer_api_id)->firstOrFail();
                    $customerName = $customerUser->first_name . ' ' . $customerUser->last_name . ' ' . $customerUser->company_name;

                } else {
                    $customerName = '-';
                }


                if (count($item->ebay_orders) > 0) {

                    foreach ($item->ebay_orders as $ebay) {
                        $postCode = strtoupper($ebay->post_to_postcode);
                        foreach ($ebay->EbayOrderItems as $ebayOrdersItem) {
                            $taxRate = 0;
                            $totalCosts = 0;
                            $vatType = '';
                            $itemsPrice = 0;
                            $purchasePriceStock = 0;

                            if ($ebayOrdersItem->quantity > 1) {
                                if (!is_null(json_decode($ebayOrdersItem->stock_id))) {
                                    foreach (json_decode($ebayOrdersItem->stock_id) as $stockId) {
                                        $totalCosts += getStockDetatils($stockId)->total_cost_with_repair;
                                        $totalPurchaseCost += getStockDetatils($stockId)->total_cost_with_repair;
                                        $purchasePrice += getStockDetatils($stockId)->purchase_price;
                                        $purchasePriceStock += getStockDetatils($stockId)->purchase_price;

                                        $vatType = getStockDetatils($stockId)->vat_type;
                                        $totalUnlockCost += getStockDetatils($stockId)->unlock_cost;
                                        $totalPartCost += getStockDetatils($stockId)->part_cost;
                                        $totalRepairCost += getStockDetatils($stockId)->total_repair_cost - getStockDetatils($stockId)->part_cost;
                                        $totalSalePriceDelivery += getStockDetatils($stockId)->sale_price;

                                        if ($ebay->platform === Stock::PLATFROM_MOBILE_ADVANTAGE || $ebay->platform === Stock::PLATFROM_EBAY) {
                                            $itemsPrice = $ebayOrdersItem['individual_item_price'] * $ebayOrdersItem['quantity'];
                                        } else {
                                            $itemsPrice = $ebayOrdersItem['individual_item_price'];
                                        }

                                        $taxRate = $ebayOrdersItem->tax_percentage * 100 > 0 ? ($ebayOrdersItem->tax_percentage) : 0;
                                    }
                                }
                            } else {
                                $tProfit = 0;
                                $fTrueProfit = 0;
                                foreach ($ebayOrdersItem->stock()->get() as $ebayOrderstock) {


                                    $totalCosts += $ebayOrderstock->total_cost_with_repair;
                                    $totalPurchaseCost += $ebayOrderstock->total_cost_with_repair;
                                    $purchasePrice += $ebayOrderstock->purchase_price;
                                    $purchasePriceStock += $ebayOrderstock->purchase_price;
                                    $vatType = $ebayOrderstock->vat_type;
                                    $totalUnlockCost += $ebayOrderstock->unlock_cost;
                                    $totalPartCost += $ebayOrderstock->part_cost;
                                    $totalRepairCost += $ebayOrderstock->total_repair_cost - $ebayOrderstock->part_cost;
                                    $itemsPrice += $ebayOrdersItem['individual_item_price'];
                                    $totalSalePriceDelivery += $ebayOrderstock->sale_price;
                                    $taxRate = $ebayOrdersItem->tax_percentage * 100 > 0 ? ($ebayOrdersItem->tax_percentage) : 0;
                                    $tProfit += $ebayOrderstock->profit;
                                    $fTrueProfit += $ebayOrderstock->true_profit;
                                    $pVatMargin += $ebayOrderstock->marg_vat;


                                }
                            }

                            if ($ebayOrdersItem->tax_percentage * 100 > 0 || !$ebayOrdersItem->tax_percentage * 100 && $vatType === "Standard") {
                                $vatType = "Standard";
                                array_push($vatTypeList, $vatType);
                            } else {
                                $vatType = "Margin";
                                array_push($vatTypeList, $vatType);
                            }

                            $ftProfit += $tProfit;
                            $ftTrueProfit += $fTrueProfit;

//                            $calculations = calculationOfProfitEbay($taxRate, $itemsPrice, $totalCosts, $vatType,$purchasePriceStock);
//                            $totalExVat+= $calculations['total_price_ex_vat'];
//                            $totalTrueProfit += $calculations['true_profit'];
//                            $totalProfit+=$calculations['profit'];
//                            $totalSalePrice+=$itemsPrice;
//                            $totalVatMargin+= $calculations['marg_vat'];


                            $calculations = calculationOfProfitEbay($taxRate, $itemsPrice, $totalCosts, $vatType, $purchasePriceStock);
                            if ($vatType === Stock::VAT_TYPE_STD) {
                                $totalProfit = ($item->invoice_total_amount / 1.2) - $totalPurchaseCost;
                                $totalTrueProfit = $item->invoice_total_amount / 1.2 - $totalPurchaseCost;
                                $totalExVat = $item->invoice_total_amount / 1.2;
                            } else {
                                $totalExVat = $calculations['total_price_ex_vat'];
                                $totalTrueProfit = $calculations['true_profit'];
                                $totalProfit = $calculations['profit'];
                                $totalSalePrice = $itemsPrice;
                                $totalVatMargin = $calculations['marg_vat'];

                            }


//                            if($ebayOrdersItem->tax_percentage * 100 > 0 || !$ebayOrdersItem->tax_percentage * 100  && $vatType==="Standard"){
//                                $totalProfit+=($item->invoice_total_amount/1.2) - $totalPurchaseCost;
//                                $totalTrueProfit+=$item->invoice_total_amount/1.2 - $totalPurchaseCost;
//                                $totalExVat+= $item->invoice_total_amount/1.2;
//                            }else{
//                                $totalProfit+=$item->invoice_total_amount - $totalPurchaseCost-($item->delivery_charges*20/100);
//                                $totalVatMargin += ((($totalSalePriceDelivery-$purchasePrice)*16.67)/100);
//
//                                $totalTrueProfit+=($item->invoice_total_amount - $totalPurchaseCost-$item->delivery_charges*20/100)- ((($totalSalePriceDelivery-$purchasePrice)*16.67)/100);
//                                $totalSalePrice+=$item->invoice_total_amount - $item->delivery_charges*20/100;
//
//                            }

                        }
                    }

                    if (!is_null($item->delivery_charges)) {
                        $totalProfit = 0;
                        $totalTrueProfit = 0;
                        $totalVatMargin = 0;
                        $totalExVat = 0;
                        $totalSalePrice = 0;
                        if ($ebayOrdersItem->tax_percentage * 100 > 0 || !$ebayOrdersItem->tax_percentage * 100 && $vatType === "Standard") {
                            $totalProfit += ($item->invoice_total_amount / 1.2) - $totalPurchaseCost;
                            $totalTrueProfit += $item->invoice_total_amount / 1.2 - $totalPurchaseCost;
                            $totalExVat += $item->invoice_total_amount / 1.2;
                        } else {
                            $totalProfit += $item->invoice_total_amount - $totalPurchaseCost - ($item->delivery_charges * 20 / 100);
                            $totalVatMargin += ((($totalSalePriceDelivery - $purchasePrice) * 16.67) / 100);

                            $totalTrueProfit += ($item->invoice_total_amount - $totalPurchaseCost - $item->delivery_charges * 20 / 100) - ((($totalSalePriceDelivery - $purchasePrice) * 16.67) / 100);
                            $totalSalePrice += $item->invoice_total_amount - $item->delivery_charges * 20 / 100;

                        }

                    }
                } else {
                    $supplierPre = '';
                    foreach ($item->stock as $stock) {
                        $totalExVat += $stock->total_price_ex_vat;
                    }
                    foreach ($item->stock as $stock) {
                        $totalPurchasePrice += $stock->purchase_price;
                    }
                    foreach ($item->stock as $stock) {
                        $totalProfit += $stock->profit;
                    }
                    foreach ($item->stock as $stock) {
                        $totalTrueProfit += $stock->true_profit;
                    }
                    foreach ($item->stock as $stock) {
                        $totalSalePrice += $stock->sale_price;
                    }
                    foreach ($item->stock as $stock) {
                        $totalVatMargin += $stock->marg_vat;
                    }
                    foreach ($item->stock as $stock) {
                        $totalPurchaseCost += $stock->total_cost_with_repair;
                    }
                    foreach ($item->stock as $stock) {
                        $purchasePrice += $stock->purchase_price;
                    }
                    foreach ($item->stock as $stock) {
                        $totalSalePriceDelivery += $stock->sale_price;
                    }
                    foreach ($item->stock as $stock) {
                        $totalUnlockCost += $stock->unlock_cost;
                    }
                    foreach ($item->stock as $stock) {
                        $totalPartCost += $stock->part_cost;
                    }
                    foreach ($item->stock as $stock) {
                        $totalRepairCost += $stock->total_repair_cost - $stock->part_cost;
                    }

                    $totalProfit = 0;
                    $totalTrueProfit = 0;
                    $totalVatMargin = 0;
                    $totalExVat = 0;
                    $totalSalePrice = 0;
                    if (count($item->stock)) {
                        if ($item->stock[0]->vat_type === "Margin") {
                            $totalProfit += $item->invoice_total_amount - $totalPurchaseCost - ($item->delivery_charges * 20 / 100);
                            $totalVatMargin += ((($totalSalePriceDelivery - $purchasePrice) * 16.67) / 100);
                            $totalTrueProfit += ($item->invoice_total_amount - $totalPurchaseCost - $item->delivery_charges * 20 / 100) - ((($totalSalePriceDelivery - $purchasePrice) * 16.67) / 100);
                            $totalSalePrice += $item->invoice_total_amount - $item->delivery_charges * 20 / 100;
                        } else {
                            $totalProfit += ($item->invoice_total_amount / 1.2) - $totalPurchaseCost;
                            $totalTrueProfit += $item->invoice_total_amount / 1.2 - $totalPurchaseCost;
                            $totalExVat += $item->invoice_total_amount / 1.2;
                        }
                    }
                }
                if (count($item->ebay_orders)) {

                    if (count(array_unique($vatTypeList)) > 1) {
                        $itemVatType = 'Mixed';
                    } else {
                        if ($item->ebay_orders[0]->EbayOrderItems[0]['tax_percentage'] * 100 > 0 || !$item->ebay_orders[0]->EbayOrderItems[0]['tax_percentage'] * 100 && $vatType === "Standard") {
                            $itemVatType = "Standard";
                        } else {
                            $itemVatType = "Margin";
                        }
                    }

                } else {
                    $itemVatType = count($item->stock) > 0 ? $item->stock[0]->vat_type : '-';
                }

                if ($item->buyers_ref !== '') {
                    $buyersRef = $item->buyers_ref;
                } else {
                    $buyersRef = '-';
                }

                if (Auth::user()->type === 'admin' && $item->stock()->count() && $item->stock()->first()->batch_id) {
                    $itemName = "Batch no " . $item->stock()->first()->batch->id . '-' . $item->stock()->first()->batch->name;

                } else {
                    $itemName = implode(', ', str_replace(array('@rt'), ' GB', $item->stock->lists('name')));
                }
//                if($item->item_name){
//                    $itemName= str_replace( array('GB'), '@rt', $item->item_name);
//                }
                if ($item->other_recycler) {
                    $shipTo = $item->other_recycler;
                } else {
                    if ($item->customer_api_id) {
                        if ($postCode !== "") {
                            $shipTo = $postCode;

                        } else {
                            if (isset($customerUser->billingAddress->postcode)) {
                                $shipTo = strtoupper($customerUser->billingAddress->postcode);
                            } elseif (isset($customerUser->address->postcode)) {
                                $shipTo = strtoupper($customerUser->address->postcode);
                            } else {
                                $shipTo = '-';
                            }

                        }

                    } else {
                        $shipTo = '-';

                    }
                }

                if (count($item->ebay_orders)) {
                    if (isset($item->ebay_orders)) {
                        if (is_null($item->delivery_charges) || $item->ebay_orders[0]->EbayOrderItems[0]['tax_percentage'] * 100 > 0 || !$item->ebay_orders[0]->EbayOrderItems[0]['tax_percentage'] * 100 && $vatType === "Standard") {
                            $saleTotalIncCarriage = $item->amount ? $item->amount_formatted : "Replacements";
                        } else {
                            $saleTotalIncCarriage = money_format(config('app.money_format'), $item->amount - ($item->delivery_charges * 20 / 100));

                        }
                    }

                } else {
                    if (count($item->stock)) {
                        if (is_null($item->delivery_charges) || $item->stock[0]->vat_type === "Standard") {
                            $saleTotalIncCarriage = $item->amount ? $item->amount_formatted : "Replacements";

                        } else {
                            $saleTotalIncCarriage = money_format(config('app.money_format'), $item->amount - ($item->delivery_charges * 20 / 100));

                        }
                    }

                }

                if (count($item->ebay_orders)) {

                    if (count(array_unique($vatTypeList)) > 1) {
                        $saleTotalexVatincCarriage = money_format(config('app.money_format'), $item->amount / 1.2);
                    } else {
                        if ($item->ebay_orders[0]->EbayOrderItems[0]['tax_percentage'] * 100 > 0 || !$item->ebay_orders[0]->EbayOrderItems[0]['tax_percentage'] * 100 && $vatType === "Standard") {
                            $saleTotalexVatincCarriage = money_format(config('app.money_format'), $item->amount / 1.2);
                        } else {
                            $saleTotalexVatincCarriage = '-';
                        }
                    }


                } else {
                    if (isset($item->stock[0]) && $item->stock[0]->vat_type === "Standard") {
                        $saleTotalexVatincCarriage = money_format(config('app.money_format'), $item->amount / 1.2);
                    } else {
                        $saleTotalexVatincCarriage = '-';
                    }

                }

                //  $totalProfitPer
                if (count($item->ebay_orders)) {
                    if (count(array_unique($vatTypeList)) > 1) {

                        $totalProfitPer = $totalSalePrice ? ($ftProfit / $totalSalePrice) * 100 : 0;

                        //  $totalProfitPer=$totalSalePrice?($totalProfit/$totalSalePrice) * 100:0 ;
                    } else {
                        if ($item->ebay_orders[0]->EbayOrderItems[0]['tax_percentage'] * 100 > 0 || !($item->ebay_orders[0]->EbayOrderItems[0]['tax_percentage'] * 100) && $vatType === "Standard") {
                            $totalProfitPer = $totalExVat ? (number_format($totalProfit, 2) / number_format($totalExVat, 2)) * 100 : 0;
                        } else {
                            $totalProfitPer = $totalSalePrice ? ($totalProfit / $totalSalePrice) * 100 : 0;
                        }
                    }
                } else {
                    if (isset($item->stock[0]) && $item->stock[0]->vat_type === "Standard") {
                        $totalProfitPer = $totalExVat ? ($totalProfit / $totalExVat) * 100 : 0;
                    } else {
                        $totalProfitPer = $totalSalePrice ? ($totalProfit / $totalSalePrice) * 100 : 0;
                    }
                }

                if (count($item->ebay_orders)) {
                    if (count(array_unique($vatTypeList)) > 1) {

                        $vatMrg = money_format(config('app.money_format'), $pVatMargin);
                    } else {
                        if (!$item->ebay_orders[0]->EbayOrderItems[0]['tax_percentage'] * 100 && $vatType === "Margin") {
                            $vatMrg = money_format(config('app.money_format'), $totalVatMargin);
                        }
                    }

                } else {
                    if (isset($item->stock[0]) && $item->stock[0]->vat_type === "Margin") {
                        $vatMrg = money_format(config('app.money_format'), $totalVatMargin);
                    } else {
                        $vatMrg = "-";
                    }

                }

//$totalTrueProfitPer
                if (count($item->ebay_orders)) {
                    if (count(array_unique($vatTypeList)) > 1) {
                        $totalTrueProfitPer = $totalSalePrice ? ($ftTrueProfit / $totalSalePrice) * 100 : 0;

                        //    $totalTrueProfitPer=$totalSalePrice?($totalTrueProfit/$totalSalePrice) * 100:0;
                    } else {
                        if ($item->ebay_orders[0]->EbayOrderItems[0]['tax_percentage'] * 100 > 0 || !$item->ebay_orders[0]->EbayOrderItems[0]['tax_percentage'] * 100 && $vatType === "Standard") {

                            $totalTrueProfitPer = $totalExVat ? (number_format($totalTrueProfit, 2) / number_format($totalExVat, 2)) * 100 : 0;
                        } else {

                            $totalTrueProfitPer = $totalSalePrice ? ($totalTrueProfit / $totalSalePrice) * 100 : 0;

                        }
                    }
                } else {

                    if (isset($item->stock[0]) && $item->stock[0]->vat_type === "Standard") {
                        $totalTrueProfitPer = $totalExVat ? ($totalTrueProfit / $totalExVat) * 100 : 0;
                    } else {
                        $totalTrueProfitPer = $totalSalePrice ? ($totalTrueProfit / $totalSalePrice) * 100 : 0;
                    }

                }
                if (count(array_unique($vatTypeList)) > 1) {
                    $estProfit = $ftTrueProfit - $item->platform_fee - $item->shipping_cost;
                } else {
                    $estProfit = $totalTrueProfit - $item->platform_fee - $item->shipping_cost;
                }

                //$totalEstProfitPre
                if (count($item->ebay_orders)) {
                    if ($item->ebay_orders[0]->EbayOrderItems[0]['tax_percentage'] * 100 > 0 || !$item->ebay_orders[0]->EbayOrderItems[0]['tax_percentage'] * 100 && $vatType === "Standard") {
                        $vat = $item->amount / 1.2;
                        $totalEstProfitPre = $item->shipping_cost > 0 ? number_format(($estProfit / $vat) * 100, 2) . "%" : '-';


                    } else {
                        if (is_null($item->delivery_charges)) {
                            $totalEstProfitPre = $item->shipping_cost > 0 && $item->amount > 0 ? number_format(($estProfit / $item->amount) * 100, 2) . "%" : '-';
                        } else {
                            $amount = $item->amount - $item->delivery_charges * 20 / 100;
                            $totalEstProfitPre = $item->shipping_cost > 0 && $amount > 0 ? number_format(($estProfit / $amount) * 100, 2) . "%" : '-';
                        }
                    }
                } else {
                    if (count($item->stock) > 0) {
                        if ($item->stock[0]->vat_type === "Standard") {
                            $vat = $item->amount / 1.2;
                            $totalEstProfitPre = $item->shipping_cost > 0 ? number_format(($estProfit / $vat) * 100, 2) . "%" : '-';
                        } else {
                            if (is_null($item->delivery_charges)) {
                                $totalEstProfitPre = $item->shipping_cost > 0 ? number_format(($estProfit / $item->amount) * 100, 2) . "%" : '-';
                            } else {
                                $amount = $item->amount - $item->delivery_charges * 20 / 100;
                                $totalEstProfitPre = $item->shipping_cost > 0 ? number_format(($estProfit / $amount) * 100, 2) . "%" : '-';

                            }
                        }

                    }

                }
                if ($item->invoice_creation_status === 'success') {

                    if (!is_null($item->invoice_doc_number)) {
                        $invoice = "Invoice #" . $item->invoice_number . "-" . $item->invoice_doc_number;
                    } else {
                        $invoice = "Invoice #" . $item->invoice_number;
                    }

                } elseif ($item->other_recycler) {
                    $invoice = "Recyclers Order #" . $item->recyclers_order_number ?: '-';
                } else {
                    $invoice = $item->invoice_creation_status_alt;
                }

                foreach ($item->stock as $stock) {
                    if ($stock->ps_model) {
                        if (!is_null($stock->supplier_id)) {


                            if (!is_null($stock->supplier->recomm_ps)) {
                                $totalItemsSoldPS++;
                                $supplierPre = $stock->supplier->recomm_ps;

                                if ($stock->vat_type === Stock::VAT_TYPE_STD) {
                                    $est = ($stock->total_price_ex_vat * $stock->supplier->recomm_ps) / 100;
                                    $estProfitSPModel += $est;
                                } else {

                                    $salePrice = $stock->sale_price - $stock->marg_vat;
                                    $estM = ($salePrice * $stock->supplier->recomm_ps) / 100;
                                    $estProfitSPModel += $estM;

                                }

                            }


                        }


                    } else {
                        $totalItemsSoldNonPS++;
                        $estProfitNonSPModel += $stock->true_profit - $item->platform_fee;
                    }
                }
                if (abs($estProfitSPModel) > 0) {
                    if (abs($estProfitNonSPModel) > 0) {
                        $totalNonPsModel += $estProfitNonSPModel + $item->delivery_charges;
                    }
                } else {
                    $totalNonPsModel += $estProfit;
                }
                if (abs($estProfitSPModel) > 0) {
                    if ($totalItemsSoldPS && !$totalItemsSoldNonPS) {

                        $totalPsModel += ($estProfit * $supplierPre) / 100;

                    } else {
                        $totalPsModel += ($estProfitSPModel + $item->delivery_charges) - $item->shipping_cost;
                    }
                }
                $psNonModel = 0;
                $psModel = 0;
                if (abs($estProfitNonSPModel) > 0) {
                    $psNonModel = $estProfitNonSPModel + $item->delivery_charges;
                }

                if (abs($estProfitSPModel) > 0) {
                    $psModel = ($estProfitSPModel + $item->delivery_charges) - $item->shipping_cost;
                }

                if ($totalItemsSoldPS && !$totalItemsSoldNonPS) {
                    $totalNetProfit = ($estProfit * $supplierPre) / 100;
                } elseif ($estProfitSPModel > 0) {
                    $totalNetProfit = $psNonModel + $psModel;

                } else {
                    $totalNetProfit = $estProfit;
                }
                foreach ($item->stock as $stock) {
                    $PStotalExVat += $stock->total_price_ex_vat;
                }
                foreach ($item->stock as $stock) {
                    $PSTotalSalePrice += $stock->sale_price;
                }
                foreach ($item->stock as $stock) {
                    $PSTotalVatMargin += $stock->marg_vat;
                }

                if (count($item->ebay_orders)) {
                    if ($item->ebay_orders[0]->EbayOrderItems[0]['tax_percentage'] * 100 > 0 || !$item->ebay_orders[0]->EbayOrderItems[0]['tax_percentage'] * 100 && $vatType === "Standard") {
                        $fVatType = Stock::VAT_TYPE_STD;
                    } else {
                        $fVatType = Stock::VAT_TYPE_MAG;
                    }
                } else {
                    if (isset($item->stock[0]) && $item->stock[0]->vat_type === "Standard") {
                        $fVatType = Stock::VAT_TYPE_STD;
                    } else {
                        $fVatType = Stock::VAT_TYPE_MAG;
                    }
                }
                if ($fVatType === Stock::VAT_TYPE_STD) {
                    $finaNetProfit += $PStotalExVat > 0 ? ($totalNetProfit / $PStotalExVat) * 100 : 0;
                } else {
                    $totalVatAndSalePrice = $PSTotalSalePrice - $PSTotalVatMargin;
                    $finaNetProfit += $totalVatAndSalePrice > 0 ? ($totalNetProfit / $totalVatAndSalePrice) * 100 : 0;
                }
                if (count($item->stock)) {
                    $item->created_at = Carbon::createFromFormat('Y-m-d H:i:s', $item->created_at)->format('Y-m-d');
                    $item->customer = $customerName;
                    $item->ship_to = $shipTo;
                    $item->item_count = count($item->stock) > 0 ? count($item->stock) : 0;
                    $item->buyers_ref = $buyersRef;
                    $item->item_name = $itemName;
                    $item->vat_type = $itemVatType;
                    $item->sale_carriage = $saleTotalIncCarriage;
                    $item->sale_total_ex_vat = $saleTotalexVatincCarriage;
                    $item->total_purchase_cost = $totalPurchaseCost > 0 ? money_format(config('app.money_format'), $totalPurchaseCost) : '-';
                    $item->profit = $itemVatType === "Mixed" ? money_format(config('app.money_format'), $ftProfit) : money_format(config('app.money_format'), $totalProfit);
                    $item->profit_per = number_format($totalProfitPer, 2) . "%";
                    $item->margin_vat = $vatMrg;
                    $item->invoice = $invoice;
                    $item->true_profit = $itemVatType === "Mixed" ? money_format(config('app.money_format'), $ftTrueProfit) : money_format(config('app.money_format'), $totalTrueProfit);
                    $item->true_profit_pre = number_format($totalTrueProfitPer, 2) . "%";

                    $item->est_net_profit = money_format(config('app.money_format'), $estProfit);
                    //  $item->est_net_profit_pre=$totalEstProfitPre;
                    $item->est_net_profit_non_ps = money_format(config('app.money_format'), $totalNonPsModel);
                    $item->est_net_profit_ps = money_format(config('app.money_format'), $totalPsModel);
                    $item->items_sold_non_ps = $totalItemsSoldNonPS;
                    $item->items_sold_ps = $totalItemsSoldPS;
                    $item->total_est_net_profit = money_format(config('app.money_format'), $totalNetProfit);
                    $item->net_profit_pre = number_format($finaNetProfit, 2) . '%';

                    $row = array_map(function ($field) use ($item) {
                        return $item->$field;
                    }, $fields);
                    fputcsv($fh, $row);

                }


            }
        });


        fclose($fh);
        convert_file_encoding($csvPath);
        header('Content-length: ' . filesize($csvPath));
        header('Content-Disposition: attachment; filename="Sales_export.csv"');
        header('Content-type: application/vnd.ms-excel');
        readfile($csvPath);
    }


    public function deliveryNoteDownload($id)
    {

        $delivery = DeliveryNotes::where('sales_id', $id)->first();
        $sales = Sale::find($id);
        $ebayOrder = EbayOrders::where('new_sale_id', $id)->first();
        $buyer_name = '';
        $shipping_name = '';
        $stockList = '';
        $note = '';
        $atArray = [];
        $condition = '';

        if (!is_null($ebayOrder)) {
            $note .= "Notes:" . str_replace(' ', '', $ebayOrder->platform) . " Order: " . $ebayOrder->sales_record_number;


            foreach ($ebayOrder->EbayOrderItems as $item) {
                // $condition=$item->quantity." X ";

                if ($ebayOrder->platform === Stock::PLATFROM_BACKMARCKET) {
                    $condition = getBackMarketCondition($item->condition);
                    $stockList .= $item->quantity . " X " . ' ';
                }


            }
            //   dd($note);
            if (count($sales->stock()->get())) {
                foreach ($sales->stock()->get() as $stock) {
                    $stock_name = $stock->name . ' ' . $stock->capacity . '-' . $stock->colour . ' ' . $condition;
                    array_push($atArray, ($item->quantity / count($sales->stock()->get())) . " X " . ' ' . $stock_name);


                }
            }
        }


        if (!is_null($ebayOrder)) {
            $buyer_name = $ebayOrder->buyer_name;
            $shipping_name = $ebayOrder->post_to_name;

        } else {
            if ($sales->platform === Stock::PLATFROM_RECOMM) {
                $buyer_name = $sales->user->first_name . ' ' . $sales->user->last_name;
                $shipping_name = $sales->user->first_name . ' ' . $sales->user->last_name;
            }
        }
        if (!is_null($delivery)) {
            $pdf = PDF::loadView('sales.delivery-note-pdf', compact('delivery', 'sales', 'buyer_name', 'shipping_name', 'note', 'atArray', 'condition'));
            return $pdf->download('delivery_note.pdf');
        }


        // return view('sales.delivery-note-pdf',compact('delivery','sales','buyer_name','shipping_name','note','atArray','condition'));
    }
}
