<?php

namespace App\Http\Controllers;
use App\Batch;
use App\Models\BillingAddress;
use App\Commands\Users\AccountSuspended;
use App\Contracts\Invoicing;
use App\Models\Invoice;
use App\Models\Sale;
use App\Models\User;
use App\Models\Unlock\Order;
use App\Models\User\Address;
use App\Models\UserLog;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;




class UserController extends Controller
{
    protected $auth;

    public function __construct(Guard $guard) {
        $this->auth = $guard;
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function getIndex(Request $request) {



//        $currency = Number::currency(1000);
//        dd($currency);


        $users = $this->getUserQuery($request)->paginate(config('app.pagination'));

        return view('admin.users.index', compact('users'));

//        if ($request->ajax()) {
//            return response()->json([
//                'usersHtml' => View::make('admin.users.list', compact('users'))->render(),
//                'paginationHtml' => '' . $users->appends($request->all())->render(),
//            ]);
//        } else {
//            return view('admin.users.index', compact('users'));
//        }
    }

    /**
     * @param Request $request
     * @return mixed
     */
    protected function getUserQuery(Request $request) {
        $query = User::where('suspended',0)->whereIn('type',['user', 'manager', 'admin'])->orderBy('id','DESC');

        if ($request->term) {
            $query->where(function($subQuery) use ($request) {
                $subQuery->where('first_name', 'like', "%$request->term%");
                $subQuery->orWhere('last_name', 'like', "%$request->term%");
                $subQuery->orWhere('email', 'like', "%$request->term%");
                $subQuery->orWhere('phone', 'like', "$request->term%");
                $subQuery->orWhere('invoice_api_id', 'like', "%$request->term%");
            });
        }

        if ($request->filter) {
            if ($request->filter == 'never_bought') {
                $usersNeverBoughtIds = Sale::groupBy('user_id')->get()->lists('user_id');
                $query->whereNotIn('id', $usersNeverBoughtIds);
            } elseif ($request->filter == 'not_last_45_days') {
                $date45daysAgo = Carbon::now()->subDays(45)->startOfDay();
                $usersEverBoughtIds = Sale::groupBy('user_id')->get()->lists('user_id');
                $usersLast45daysIds = Sale::where('created_at', '>=', $date45daysAgo)->groupBy('user_id')->get()->lists('user_id');
                $query->whereIn('id', $usersEverBoughtIds)->whereNotIn('id', $usersLast45daysIds);
            }
        }

        if ($request->country) {
            $query->whereHas('address', function($q) use($request) {
                $q->where('country', $request->country);
            });
        }

        if ($request->customer_type) {
            $query->where('customer_type', $request->customer_type);
        }

        return $query;
    }

    /**
     * @param Request $request
     * @param $id
   //  * @param Invoicing $invoicing
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\JsonResponse
     */
    public function getSingle(Request $request, $id, Invoicing $invoicing) {



       // dd(config('services.quickbooks.oauth2.client_id'));

        ini_set('memory_limit', '1024M');
        $user = User::with('document')->findOrFail($id);

        $subAdmin=User::where('type','sub-admin')->where("master_admin_id",$id)->paginate(10);
        if ($request->ajax()) {
            return response()->json([
                'balance' => $user->balance_formatted,
            ]);
        }

        if ($request->user_id) {
            $user->vat_number = $request->vat_number;
            $user->customer_bio = $request->customer_bio;
            $user->save();
        }
        $customer = $user->invoice_api_id ? $invoicing->getCustomer($user->invoice_api_id) : null;
        $orders = $user->unlock_orders()->orderBy('id', 'desc')->get();

        $sales = $user->invoice_api_id ? Sale::with('stock')->where('customer_api_id', $user->invoice_api_id)->orderBy('id', 'desc')->get() : null;

        $total_spend_sales = 0;
        $total_spend_unlocks = 0;
        if ($user->invoice_api_id) {
            $total_spend_sales = Sale::where('customer_api_id', $user->invoice_api_id)
                ->whereIn('invoice_status', [Invoice::STATUS_OPEN, Invoice::STATUS_PAID, Invoice::STATUS_DISPATCHED . Invoice::STATUS_READY_FOR_DISPATCH])
                ->sum('invoice_total_amount');
            $total_spend_unlocks = Order::where('customer_api_id', $user->invoice_api_id)
                ->whereIn('invoice_status', [Invoice::STATUS_OPEN, Invoice::STATUS_PAID, Invoice::STATUS_DISPATCHED . Invoice::STATUS_READY_FOR_DISPATCH])
                ->sum('invoice_total_amount');
        }

        $total_spend = $total_spend_sales + $total_spend_unlocks;
        $emails_count = 0;

        return view('admin.users.single', compact('invoicing', 'user', 'customer', 'orders', 'sales', 'total_spend', 'emails_count','subAdmin'));
    }

    public function postLogin(Request $request) {
        $previousUser = Auth::user();
        $user = User::findOrFail($request->id);
        $this->auth->login($user);
        session(['users.previous' => $previousUser]);
        return redirect('stock')->with('messages.success', "You're now logged in as $user->full_name.");
    }


    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|void
     */
    public function removeDeleted(Request  $request){

        $user= User::find($request->id);

        if(!is_null($user)){
            $user->delete();
            return redirect(route('admin.users'))->with('messages.success','successfully User Removed');
        }


    }
    public function postApiGenerateKey(Request $request) {
        $user = User::findOrFail($request->user_id);
        $user->api_key = md5(rand()) . md5(rand());
        $user->save();
        return back()->with('messages.success', "API key has been set.");
    }

    public function postMarketingEmails(Request $request) {
        $user = User::findOrFail($request->id);
        $user->fill($request->only(['marketing_emails_subscribe']));
        $user->save();

        $message = "User's marketing emails settings have been saved.";

        if (!$user->marketing_emails_subscribe && $user->whatsapp) {
            $user->whatsapp = 0;
            $user->save();
            $message .= " Whatsapp has been disabled. (You need to reload the page to update Whats App Yes/No toggle";
        }

        return response()->json([
            'status' => 'success',
            'message' => $message,
        ]);
    }

    public function postSuspendUser(Request $request) {
        $user = User::findOrFail($request->id);

        $original = $user->suspended;
        $current = $request->suspended;
        if ($original == $current) {
            return back()->with('messages.info', 'Requested Account Suspended value is same as the original, nothing changed');
        }

        $user->suspended = $request->suspended;
        $user->save();

        $user->suspended == 1 ? $content = "Suspend" : $content = "Unsuspend";
        $content .= " . Auth User: " . Auth::user()->id . " " . Auth::user()->full_name;
        $userLog = new \App\Models\UserLog();
        $userLog->user_id = $user->id;
        $userLog->content = $content;
        $userLog->save();


        $suspendedBy = Auth::user()->first_name;

        Queue::pushOn('emails', new AccountSuspended($user, $suspendedBy));

        $message = "User has been ";
        $user->suspended == 1 ? $message .= "Suspended" : $message .= "Unsuspended";
        return back()->with('messages.success', $message);
    }

    public function postCreateQuickbooksCustomer(Request $request, Invoicing $invoicing) {
        $user = User::with(['address','billingAddress'])->findOrFail($request->id);


        $e = "";

        $customer = $invoicing->getCustomers()->where('email', $user->email)->first();
        if ($customer) {
            $user->invoice_api_id = $customer->external_id;
            $user->save();
        }


        $isRegular = !$user->type || $user->type === 'user';
        if ($isRegular && !$user->invoice_api_id) {

            $customer = $user->getCustomer();

            $attempts = 0;
            do {
                $attempts++;
                try {

                    $user->invoice_api_id = $invoicing->createCustomer($customer);
                    break;
                } catch (Exception $e) {
                    if ($attempts <= 3 && strpos($e, 'The name supplied already exists') !== false) {
                        preg_match('/ \((\d+)\)$/', $customer->last_name, $dupeIdxMatch);
                        $dupeIdx = $dupeIdxMatch ? $dupeIdxMatch[1] + 1 : 2;
                        $customer->last_name = preg_replace('/ \(\d+\)$/', '', $customer->last_name);
                        $customer->last_name .= " ($dupeIdx)";
                    } elseif ($attempts > 3) {
                        $e = $e->getMessage();
                        break;
                    }
                }
            } while (true);
            $user->save();
        }else{

            $customer = $user->getCustomer($user->invoice_api_id);
            if($user->billingAddress){
                $customer->billing_address = new BillingAddress($user->billingAddress->toArray());
            }else{

                $customer->billing_address = new Address($user->address->toArray());
            }


            $customer->shipping_address = new Address($user->address->toArray());
            $invoicing->updateCustomer($customer);



        }

        if ($user->invoice_api_id) {
            $type = "success";
            $message = "Customer has been created.";
        } else {
            $type = "error-custom";
            $message = "Something Went Wrong\n$e";
        }

        return back()->with("messages.$type", $message);
    }

    public function getSyncQuickbooksCustomer($id, Invoicing $invoicing) {
        $user = User::with(['address','billingAddress'])->findOrFail($id);

        $e = "";

        $customer = $invoicing->getCustomers()->where('email', $user->email)->first();
        if ($customer) {
            $user->invoice_api_id = $customer->external_id;
            $user->save();
        }



        $isRegular = !$user->type || $user->type === 'user';
        if ($isRegular && !$user->invoice_api_id) {
            $customer = $user->getCustomer();
            $attempts = 0;
            do {
                $attempts++;
                try {
                    $user->invoice_api_id = $invoicing->createCustomer($customer);
                    break;
                } catch (Exception $e) {
                    if ($attempts <= 3 && strpos($e, 'The name supplied already exists') !== false) {
                        preg_match('/ \((\d+)\)$/', $customer->last_name, $dupeIdxMatch);
                        $dupeIdx = $dupeIdxMatch ? $dupeIdxMatch[1] + 1 : 2;
                        $customer->last_name = preg_replace('/ \(\d+\)$/', '', $customer->last_name);
                        $customer->last_name .= " ($dupeIdx)";
                    } elseif ($attempts > 3) {
                        $e = $e->getMessage();
                        break;
                    }
                }
            } while (true);
            $user->save();
        }else{

            $customer = $user->getCustomer($user->invoice_api_id);
            if($user->billingAddress){
                $customer->billing_address = new BillingAddress($user->billingAddress->toArray());
            }else{
                $customer->billing_address = new \App\Models\Address($user->address->toArray());
            }


            $customer->shipping_address = new Address($user->address->toArray());
            $invoicing->updateCustomer($customer);



        }

        if ($user->invoice_api_id) {
            $type = "success";
            $message = "Customer has been created.";
        } else {
            $type = "error-custom";
            $message = "Something Went Wrong\n$e";
        }

        return back()->with("messages.$type", $message);
    }

    public function postUpdateBillingAddress(Request $request,Invoicing $invoicing){

        $user = User::findOrFail($request->id);

        $billingAddress = BillingAddress::firstOrNew(['user_id' => $request->id]);
        $billingAddress->user()->associate($user);
        $billingAddress->fill($request->except('id'));
        $billingAddress->save();

        $this->getSyncQuickbooksCustomer($request->id,$invoicing);
        return back()->with('messages.success', 'Billing Address has been updated.');


    }




}