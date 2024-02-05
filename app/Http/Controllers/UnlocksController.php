<?php

namespace App\Http\Controllers;

use App\Commands\Unlocks\Emails\OwnStock\NewOrder;
use App\Commands\Unlocks\Emails\OwnStock\OrderPaymentReceived;
use App\Jobs\Unlocks\Emails\UnknownNetwork;
use App\Commands\Unlocks\Emails\Unlocked;
use App\Commands\Unlocks\InvoiceUnlockCreate;
use App\Contracts\Invoicing;
use App\Http\Requests\ImeiRequest;
use App\Models\Stock;
use App\Models\StockLog;
use App\Models\Unlock;
use App\Models\Unlock\Order;
use App\Models\Unlock\Pricing;
use App\Models\User;
use App\Validation\Unlocks\OrderValidator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;

class UnlocksController extends Controller
{
    public function getIndex(Request $request)
    {
        if (Auth::user()->type === 'user') {
            return $this->getAdd($request);
        }

        $unlocks = Unlock::with('user'/*, 'orders'*/)
            ->status($request->status)
            ->imei($request->imei)
            ->network($request->network)
            ->orderBy('id', 'desc');

        if($request->stock_id) {
            $ref = $request->stock_id;
            if (strlen($ref) > 3 && strtolower(substr($ref, 0, 3)) === 'rct') {
                $ref = substr($ref, 3);
            }
            $unlocks->where('stock_id', 'like', "%$ref%");
        }

        /*if($request->source && $request->source == 'retail_orders') {
            $unlocks->has('orders.retailOrder');
        } elseif($request->source && $request->source == 'stock') {
            $unlocks->where(function($q) {
                $q->doesntHave('orders');
                $q->orWhere(function($o) {
                    $o->whereDoesntHave('orders.retailOrder');
                });
            });
        }*/

        $unlocks = $unlocks->paginate(config('app.pagination'))
            ->appends($request->all());

        return $request->ajax()
            ? [
                'itemsHtml' => View::make('unlocks.list', compact('unlocks'))->render(),
                'paginationHtml' => '' . $unlocks->render(),
            ]
            : view('unlocks.index', compact('unlocks', 'request'));
    }

    public function postAddByStock(Request $request)
    {
        $networks = Stock::getAdminUnlockableNetworks();
        $message = "";
        foreach ($request->ids as $id) {
            $item = Stock::find($id);
            if($item && $item->imei && !$item->unlock && $unlockCheck = Unlock::where('imei', $item->imei)->first()) {
                $unlockCheck->stock_id = $item->id;
                $unlockCheck->save();
                $message .= "Item $item->id - Unlock Already exists\n";
            } elseif ($item && $item->imei && !$item->unlock && in_array($item->network, $networks)) {
                $unlock = new Unlock;
                $unlock->forceFill([
                    'imei' => $item->imei,
                    'network' => $item->network,
                    'user_id' => Auth::user()->id,
                    'stock_id' => $item->id,
                ]);
                $unlock->save();
                if($request->batch == "y") {
                    $item->network = "Unlocked";
                    $item->save();
                }
                $message .= "Item $item->id - Unlock added\n";
            } elseif($item && $item->unlock) {
                $message .= "Item $item->id - Unlock Already exists\n";
                if($request->batch == "y") {
                    $item->network = "Unlocked";
                    $item->save();
                }
            } elseif($item && !$item->unlock && !in_array($item->network, $networks)) {
                $message .= "Item $item->id - Unlock Unavailable for network: $item->network \n";
            }
        }

        return back()->with('messages.success', $message);
    }

    public function postFail(Request $request)
    {
        /** @var Unlock $unlock */
        $unlock = Unlock::findOrFail($request->id);
        $unlock->status = Unlock::STATUS_FAILED;
        $unlock->status_description = $request->reason;
        $unlock->save();

        if(isset(Cache::get('notifications.unlocks')[$unlock->id])) {
            $notifications = Cache::get('notifications.unlocks');
            unset($notifications[$unlock->id]);
            Cache::put('notifications.unlocks', $notifications, 15);
        }

        return back()->with('messages.success', "Unlock marked as failed.");
    }

    public function getAdd(Request $request)
    {
        $imeiMessages = session('stock.imei_check_messages');
        return view(
            'unlocks.add-as-' . (Auth::user()->type !== 'user' ? 'admin' : 'user'),
            compact('imeiMessages') + ['imeiList' => $request->imeis ? implode("\n", $request->imeis) : '']
        );
    }

    public function postOwnStockOrderCancel(Request $request)
    {
        $order = Auth::user()->unlock_orders()->findOrFail($request->id);
        if ($order->status !== Order::STATUS_NEW) {
            return back()->with(
                'messages.error', "Status of this order is \"$order->status\" which means it can't be cancelled anymore."
            );
        }

        $order->delete();

        return back()->with('messages.success', "Order has been cancelled.");
    }

    public function getOwnStockOrderDetails($id)
    {
        $order = Auth::user()->unlock_orders()->findOrFail($id);
        return view('unlocks.own-stock.order-details', compact('order'));
    }
    // pay-submit /pay_test/
    public function postPay($orderId)
    {
        if (Auth::user()->has_incorrect_country) {
            return $this->forceAccountCountryUpdate(true, 'unlocks.own-stock');
        }

//		return $request->all();

        return $this->pay($orderId);
    }

    protected function pay($orderId)
    {
        $user = Auth::user();
        $address = $user->address;
        $order = $user->unlock_orders()->findOrFail($orderId);
//		$order = $user->orders()->findOrFail($orderId);
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
            'amount' => $order->amount,
            'currency' => 'GBP',
            'card' => $userData,
            'notifyUrl' => route('sage.notify'),
            'description' => "Order $order->id.",
            'profile' => 'LOW',
        ];
        $sageResponse = $gateway->purchase($purchaseData)->send();

        if ($sageResponse->isRedirect()) {
            session(['orders.payment_pending' => [
                'order_id' => $order->id,
                'sageResponse' => $sageResponse,
            ]]);
            return redirect()->route('unlocks.pay-get');
        }
        else {
            alert(
                "Order transaction creation error (Order id \"$order->id\"): \n" .
                print_r($sageResponse->getData(), true)
            );
            $message = "An error occurred while initiating the transaction. We've been notified and will try to resolve the " .
                "issue as soon as possible.";
            if(isset($sageResponse->getData()['StatusDetail'])) {
                $error_message = $sageResponse->getData()['StatusDetail'];
                $message .= "\n ERROR: " . $error_message;
            }
            return redirect()->route('unlocks.own-stock')->with(
                'messages.error', $message
            );
        }
    }

    public function postPaymentComplete()
    {
        $pendingPayment = session('orders.payment_pending');

        $response = redirect()->route('unlocks.own-stock');
        $order = Order::find($pendingPayment['order_id']);
        $order->status = Order::STATUS_PAID;
        $order->save();
        Queue::pushOn('emails', new OrderPaymentReceived($order));
        return $response->with('messages.success', "Order marked as paid.");

    }
    //pay get
    public function getPay()
    {
        $pendingPayment = session('orders.payment_pending');
        if (!$pendingPayment) {
            return redirect()->route('unlocks.own-stock')->with('messages.info','No payment is pending at the moment.');
        }
        return view('unlocks.pay', compact('pendingPayment'));
    }

    public function postOwnStockOrderPay(Request $request)
    {
        $order = Auth::user()->unlock_orders()->findOrFail($request->order_id);
        try {
            $charge = Stripe\Charge::create([
                'amount' => $order->amount * 100,
                'currency' => 'gbp',
                'source' => $request->stripeToken,
                'idempotency_key' => 'unlock_order_' . $order->id,
            ]);
        }
        catch (Exception $e) {
            if ($e instanceof Stripe\Error\Card) {
                return back()->with(
                    'messages.error',
                    "We got the following error message from Stripe: " . $e->getMessage()
                );
            }
            else {
                alert("Stripe unlock order payment error:\n\n$e");
                return back()->with(
                    'messages.error',
                    "An error occurred. We've been notified and will try to resolve the issue as soon as possible."
                );
            }
        }

        if (!$charge) {
            alert("Stripe unlock order payment error: charge object empty after payment for unlock order \"$order->id\".");
            return back()->with(
                'messages.error',
                "An error occurred. We've been notified and we'll try to resolve the issue as soon as possible"
            );
        }

        $order->status = Order::STATUS_PAID;
        $order->save();
        Queue::pushOn('emails', new NewOrder($order));
        return redirect()->route('unlocks.own-stock')->with('messages.success', "Order marked as paid.");
    }

    public function getOwnStockOrderPay($id)
    {
        $order = Auth::user()->unlock_orders()->findOrFail($id);

        if ($order->status !== Order::STATUS_NEW) {
            return redirect()
                ->route('unlocks.own-stock.order-details', $order->id)
                ->with('messages.warning', "This order has status \"$order->status\".");
        }

        return view('unlocks.own-stock.order-pay', compact('order'));
    }

    public function getOwnStockNewOrder()
    {
        $pricing = Pricing::groupByAmount()->get();
        return view('unlocks.own-stock.new-order', compact('pricing'));
    }

    public function postOwnStockNewOrder(Request $request, Invoicing $invoicing)
    {
        $validator = new OrderValidator(app('translator'), $request->all());
        $data = $this->validateWithObject($request, $validator);
        $imeis = $data['imeis'];

        $user = Auth::user();

        $order = new Order($data);
        $order->imeis_awaiting_payment = $imeis;
        $order->user_id = $user->id;
        $order->customer_api_id = $user->invoice_api_id;
        $order->save();

        $customerUser = User::where('invoice_api_id', $user->invoice_api_id)->firstOrFail();
        $deliveryName = $invoicing->getDeliveryForUser($customerUser);

        Queue::pushOn(
            'invoices',
            new InvoiceUnlockCreate(
                $order,
                $customerUser,
                Invoicing::UNLOCK_ORDER,
                $deliveryName
            )
        );

        return redirect()->route('unlocks.own-stock.order-details', $order->id);
    }

    public function getOwnStock()
    {
        $orders = Auth::user()->unlock_orders()->orderBy('id', 'desc')->get();
        return view('unlocks.own-stock.index', compact('orders'));
    }

    public function postAddAsAdmin(ImeiRequest $request)
    {


        $imeis = $request->imeis;
        $unlocks = Unlock::whereIn('imei', $imeis)->get()->keyBy('imei');
        $stock = Stock::whereIn('imei', $imeis)->get()->keyBy('imei');

        $messages = [];
        $done = [];
        $i = 0;

        foreach ($imeis as $imei) {
            /** @var Unlock $unlock */
            $unlock = isset($unlocks[$imei]) ? $unlocks[$imei] : null;

            if (!empty($done[$imei])) {
                $messages[] = [
                    'htmlClass' => 'warning',
                    'text' => "IMEI $imei: Already on the list (rows " . ($i + 1) . " and {$done[$imei]} are the same). Skipping.",
                ];
            }
            elseif (!$unlock) {
                $network = $request->network;
                if(isset($stock[$imei]) && $stock[$imei]->network == "Unlocked") {
                    $network = "Unknown";
                }
                $unlock = new Unlock();
                $unlock->forceFill([
                    'imei' => $imei,
                    'network' => $network,
                    'user_id' => $request->user_id ?: null,
                    'stock_id' => isset($stock[$imei]) ? $stock[$imei]->id : null,
                    'ebay_user_id' => $request->ebay_user_id ?:null,
                    'ebay_user_email' => $request->ebay_user_email ?: null,
                    'status_description'=>'',
                    'cost_added'=>0.00,
                    'processing_email_sent'=>false,
                    'fail_reported'=>false,
                    'check_status'=>'',
                    'check_data'=>'',
                    'item_name'=>''
                ]);
                $unlock->save();
                if(isset($stock[$imei])) {
                    StockLog::create([
                        'user_id' => Auth::user()->id,
                        'stock_id' => $stock[$imei]->id,
                        'content' => "Added to Unlocks (Network: $network)",
                    ]);
                }
                $messages[] = [
                    'htmlClass' => 'info',
                    'text' => "IMEI $imei: Added to unlocks.",
                ];





                dispatch(new UnknownNetwork($unlock));
              //  UnknownNetwork::dispatch()->onQueue('emails')->withData($unlock);
                if($request->network == "Unknown" && ($request->user_id != null || $request->ebay_user_email != null)){
                    Queue::pushOn('emails', new UnknownNetwork($unlock));
                }
            }
            elseif (in_array($unlock->status, [Unlock::STATUS_NEW, Unlock::STATUS_PROCESSING])) {
                $messages[] = [
                    'htmlClass' => 'info',
                    'text' => "IMEI $imei: Already processing.",
                ];
            }
            elseif ($unlock->status === Unlock::STATUS_FAILED) {
                $messages[] = [
                    'htmlClass' => 'danger',
                    'text' => "IMEI $imei: We already tried to unlock this device and failed.",
                ];
            }
            elseif ($unlock->status === Unlock::STATUS_UNLOCKED) {
                $messages[] = [
                    'htmlClass' => 'success',
                    'text' => "IMEI $imei: This device from our records is already unlocked.",
                ];
            }
            else {
                alert("IMEI check for \"$imei\" - unexpected result.");
                $messages[] = [
                    'htmlClass' => 'danger',
                    'text' => "IMEI $imei: error occurred when retrieving information for this device. We’ve been " .
                        "notified and will try to resolve the issue shortly.",
                ];
            }

            $done[$imei] = $i + 1;
            $i++;
        }

        return back()->withInput()->with('stock.imei_check_messages', $messages);
    }

    public function postAddAsUser(ImeiRequest $request)
    {
        $imeis = $request->imeis;
        $stock = Stock::whereIn('imei', $imeis)->get()->keyBy('imei');
        $user = Auth::user();
        $companyName = config('app.company_name');

        $messages = [];
        $done = [];
        $i = 0;

        foreach ($imeis as $imei) {
            /** @var Stock $item */
            $item = isset($stock[$imei]) ? $stock[$imei] : null;

            if (!empty($done[$imei])) {
                $messages[] = [
                    'htmlClass' => 'warning',
                    'text' => "IMEI $imei: Already on the list (rows " . ($i + 1) . " and {$done[$imei]} are the same). Skipping.",
                ];
            }
            elseif (!$item) {
                $messages[] = [
                    'htmlClass' => 'danger',
                    'text' => "IMEI $imei: Sorry, this device doesn’t belong to $companyName, we therefore cannot unlock it for free.",
                ];
            }
            elseif (!$item->sale || $item->sale->user_id !== $user->id) {
                $messages[] = [
                    'htmlClass' => 'danger',
                    'text' => "IMEI $imei not found in your orders.",
                ];
            }
            elseif ($item->sale->created_at->diffInDays(Carbon::now()) > 30) {
                $messages[] = [
                    'htmlClass' => 'danger',
                    'text' => "Order for IMEI $imei was made over 30 ago.",
                ];
            }
            elseif (!$item->sale->paid) {
                $messages[] = [
                    'htmlClass' => 'danger',
                    'text' => "Invoice containing IMEI $imei is not paid yet.",
                ];
            }
            elseif (!$item->free_unlock_eligible) {
                $messages[] = [
                    'htmlClass' => 'danger',
                    'text' => "IMEI $imei is not eligible for free unlock.",
                ];
            }
            elseif (!$item->unlock) {
                $unlock = new Unlock([
                    'imei' => $imei,
                    'network' => $item->network,
                ]);
                $unlock->stock_id = $item->id;
                $unlock->save();
                $messages[] = [
                    'htmlClass' => 'info',
                    'text' => "IMEI $imei: We’ve received your unlock request and we will email you as soon as the device is unlocked.",
                ];
                if($request->network == "Unknown"){
                    Queue::pushOn('emails', new UnknownNetwork($unlock));
                }
            }
            elseif (in_array($item->unlock->status, [Unlock::STATUS_NEW, Unlock::STATUS_PROCESSING])) {
                $messages[] = [
                    'htmlClass' => 'info',
                    'text' => "IMEI $imei: We already have your unlock processing and we will email you shortly.",
                ];
            }
            elseif ($item->unlock->status === Unlock::STATUS_FAILED) {
                $messages[] = [
                    'htmlClass' => 'danger',
                    'text' => "IMEI $imei: We weren’t able to unlock your device. Please contact us if you believe that to be an error.",
                ];
            }
            elseif ($item->unlock->status === Unlock::STATUS_UNLOCKED) {
                $messages[] = [
                    'htmlClass' => 'success',
                    'text' => "IMEI $imei: Your device from our records is already unlocked, please go ahead and restore the device.",
                ];
            }
            else {
                alert("IMEI check for \"$imei\" - unexpected result.");
                $messages[] = [
                    'htmlClass' => 'danger',
                    'text' => "IMEI $imei: error occurred when retrieving information for this device. We’ve been " .
                        "notified and will try to resolve the issue shortly.",
                ];
            }

            $done[$imei] = $i + 1;
            $i++;
        }

        return back()->withInput()->with('stock.imei_check_messages', $messages);
    }

    public function postMarkUnlocked(Request $request)
    {
        $unlock = Unlock::findOrFail($request->id);
        $unlock->status = Unlock::STATUS_UNLOCKED;
        Queue::pushOn('emails', new Unlocked($unlock));
        $unlock->save();
        return back()->with('messages.success', "IMEI '$unlock->imei' marked as unlocked.");
    }

    public function postRetry(Request $request)
    {
        $unlock = Unlock::findOrFail($request->id);
        $unlock->network = $request->network;
        $unlock->status = Unlock::STATUS_NEW;
        $unlock->status_description = '';
        $unlock->processing_email_sent = false;
        $unlock->fail_reported = false;
        $unlock->save();
        return back()->with('messages.success', "We'll attempt to unlock the IMEI '$unlock->imei' again.");
    }

    public function postBulkRetry(Request $request)
    {
        if(!$request->ids) {
            return back()->with('messages.error', 'Nothing Selected');
        }
        $unlocks = Unlock::whereIn('id', $request->ids)->get();

        $message = "We'll attempt to unlock the following IMEIs again:";

        foreach($unlocks as $unlock) {
            $unlock->network = $request->network;
            $unlock->status = Unlock::STATUS_NEW;
            $unlock->status_description = '';
            $unlock->processing_email_sent = false;
            $unlock->fail_reported = false;
            $unlock->save();

            $message .= "\n $unlock->imei";
        }

        return back()->with('messages.success', $message);
    }

    public function getInvoice($orderId, Invoicing $invoicing)
    {
        $order = Order::findOrFail($orderId);
        $invoicePath = $invoicing->getUnlockOrderInvoiceDocument($order);
        header('Content-type: application/pdf');
        readfile($invoicePath);
        die;
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

    public function postRetryPlaceUnlockOrderCron()
    {
        $ps = `ps aux`;
        if (strpos($ps, 'artisan imeis:place-checkmynetwork-unlock-order') !== false) {
            return back()->with('messages.warning', "Cron is already running.");
        }

        artisan_call_background('imeis:place-checkmynetwork-unlock-order');

        return back()->with('messages.success', 'Cron has been started');
    }

    public function postUpdateItemName(Request $request)
    {
        $unlock = Unlock::whereNull('stock_id')->findOrFail($request->id);

        $unlock->item_name = $request->item_name;
        $unlock->save();

        return back()->with('messages.success', 'Unlock Item Name Updated');
    }
}
