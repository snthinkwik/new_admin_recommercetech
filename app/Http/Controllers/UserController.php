<?php

namespace App\Http\Controllers;
use App\Models\EmailTracking;
use App\Http\Requests\UserRequest;
use App\Models\Batch;
use App\Models\BillingAddress;
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
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use App\Jobs\Users\AccountSuspended;
use App\Models\EmailFormat;




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

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
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

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postApiGenerateKey(Request $request) {
        $user = User::findOrFail($request->user_id);
        $user->api_key = md5(rand()) . md5(rand());
        $user->save();
        return back()->with('messages.success', "API key has been set.");
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
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

       // Queue::pushOn('emails', new AccountSuspended($user, $suspendedBy));


        dispatch(new AccountSuspended($user, $suspendedBy));
        $message = "User has been ";
        $user->suspended == 1 ? $message .= "Suspended" : $message .= "Unsuspended";
        return back()->with('messages.success', $message);
    }

    /**
     * @param Request $request
     * @param Invoicing $invoicing
     * @return \Illuminate\Http\RedirectResponse
     */
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

    /**
     * @param $id
     * @param Invoicing $invoicing
     * @return \Illuminate\Http\RedirectResponse
     */
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

    /**
     * @param Request $request
     * @param Invoicing $invoicing
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postUpdateBillingAddress(Request $request,Invoicing $invoicing){

        $user = User::findOrFail($request->id);

        $billingAddress = BillingAddress::firstOrNew(['user_id' => $request->id]);

        $billingAddress->user()->associate($user);
        $billingAddress->fill($request->except('id'));
        $billingAddress->save();

        $this->getSyncQuickbooksCustomer($request->id,$invoicing);
        return back()->with('messages.success', 'Billing Address has been updated.');


    }

    /**
     * @param UserRequest $request
     * @param Invoicing $invoicing
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postSave(UserRequest $request,Invoicing $invoicing) {
        $user = $request->id ? User::findOrFail($request->id) : new User;
        $user->fill($request->except('password'));
        $user->invoice_api_id = $request->user_invoice_api_id;

        if ($request->password) {
            $user->password = bcrypt($request->password);
        }
        if ($request->balance_spent) {
            $user->balance_spent = $request->balance_spent;
        }

        if ($request->notes)
            $user->notes = $request->notes;

        $user->registered = true;
        $user->save();


        if($user->invoice_api_id){
            $customer = $user->getCustomer($user->invoice_api_id);
            $invoicing->updateCustomer($customer);
        }


        return back()->with('messages.success', "User saved.");
    }

    /**
     * @param Request $request
     * @param Invoicing $invoicing
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postUpdateAddress(Request $request,Invoicing $invoicing) {
        $user = User::findOrFail($request->id);
        $address = $user->address;


        $address = \App\Models\User\Address::firstOrNew(['user_id' => $request->id]);
        $address->user()->associate($user);
        $address->fill($request->except('id'));
        $address->save();

        $this->getSyncQuickbooksCustomer($request->id,$invoicing);

        return back()->with('messages.success', 'Shipping Address has been updated.');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public  function sendEmail(Request $request){

        $user= User::find($request->id);

        if(!is_null($user)){
            $emailFormat=EmailFormat::where('email_format_name','multiple-upload-document')->first();


            $subject=$emailFormat->subject;
            $email=$user->email;
            $name=$user->first_name.' '.$user->last_name;

            $userId = Crypt::encrypt($user->id);

            $content= str_replace("{userId}",$userId,$emailFormat->message);


            Mail::send(
                'emails.upload-document-email',
                ['body'=>$content,'regard'=>$emailFormat->regard],
                function(Message $mail) use ($subject,$email,$name) {
                    $mail->subject($subject)
                        ->to($email, $name)
                        ->from(config('mail.from.address'), config('mail.from.name'));

                }
            );
        }


        return back()->with('messages.success','successfully Email Send');

    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function  addSubAdmin(Request  $request){
        $user= new User();
        $user->type='sub-admin';
        $user->admin_type='sub-admin';
        $user->first_name=$request->first_name;
        $user->last_name=$request->last_name;
        $user->email=$request->email;
        $user->password = bcrypt($request->password);
        $user->master_admin_id=$request->master_id;
        $user->save();
        return back()->with('messages.success','Sub Admin Successfully added');
    }

    /**
     * @param $id
     * @return \Illuminate\Http\RedirectResponse|void
     */
    public function  removeSubAdmin($id){

        $subAdmin=User::where('id',$id)->where('type','sub-admin');
        if(!is_null($subAdmin)){
            $subAdmin->delete();
            return back()->with('messages.success','Sub Admin Successfully deleted');
        }

    }


    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function getWhatsAppUsers() {
        $users = User::where('whatsapp', true)->where('whatsapp_added', false)->get();

        return view('admin.users.whats-app-users', compact('users'));
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function getNewUserForm(/* Invoicing $invoicing */) {
        $user = new User();

        return view('admin.users.new-user', compact('user'));
    }

    /**
     * @param Request $request
     * @param Invoicing $invoicing
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postCreateNewUser(Request $request,Invoicing $invoicing) {
        $user = User::withUnregistered()->where('email', $request->email)->first() ? : new User();
        $user->fill($request->except('password'));
        $user->password = bcrypt($request->password);
        $user->email_confirmation = md5(rand());
        $user->registered = true;
        $user->registration_token = null;

        $user->save();
        if (array_filter($request->address)) {
            $address = new \App\Models\User\Address(convert_special_characters($request->address));
            $address->user()->associate($user);
            $address->save();
        }

        if(array_filter($request->billing_address)){
            $billing_address=new \App\Models\User\BillingAddress(convert_special_characters($request->billing_address));
            $billing_address->user()->associate($user);
            $billing_address->save();

        }


        $this->getSyncQuickbooksCustomer($user->id, $invoicing);

        return redirect()->route('admin.users.single', ['id' => $user->id])->with('messages.success', 'User Created');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function getBulkAdd(Request $request) {
        return view('admin.users.bulk-add', compact('request'));
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postBulkAdd(Request $request) {
        $countAdded = 0;
        $skipped = [];
        $emails = preg_split('/[\s,]+/', $request->emails_raw, -1, PREG_SPLIT_NO_EMPTY);

        if (!$emails) {
            Session::flash('Required', "email filed required");
            return back();
        }

        $request->merge(['emails' => $emails]);


        $existing = User::withUnregistered()
            ->selectRaw('lower(email) email')
            ->whereIn(DB::raw('lower(email)'), $emails)
            ->pluck('email')->toArray();
        if (count($existing)) {
            $existing = array_combine($existing, array_fill(0, count($existing), true));
        }


        $emailList = [];
        $i = 0;
        $errorList = [];
        foreach ($emails as $email) {

            $i++;

            $existEmail = DB::table('users')->where('email', $email)->get();
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $invalidateEmail = ["#" . $i . " must be a valid email address"];
                array_push($errorList, $invalidateEmail);
            } elseif (count($existEmail) > 0) {
                $alreadyTaken = ["#" . $i . " email already taken"];
                array_push($errorList, $alreadyTaken);
            } else {
                if (empty($existing[$email])) {
                    $user = new User;
                    $user->forceFill([
                        'email' => $email,
                        'marketing_emails_subscribe' => true,
                        'registered' => false,
                        'registration_token' => md5(rand()),
                        'stock_fully_working' => true,
                        'stock_minor_fault' => true,
                        'stock_major_fault' => true,
                        'stock_no_power' => true,
                        'stock_icloud_locked' => true,
                    ]);
                    $existing[$email] = true;
                    $user->save();
                    if ($request->country) {
                        $address = new \App\Models\User\Address();
                        $address->user()->associate($user);
                        $address->country = $request->country;
                        $address->save();
                    }
                    $countAdded++;
                    array_push($emailList, $email);
                } else {


                    $alreadyTaken = ["#" . $i . " email already taken"];
                    array_push($errorList, $alreadyTaken);

                    $skipped[] = $email;
                }
            }
        }
        $messageParts = [];
        if (count($errorList) > 0) {
            $messageParts['error'] = ["error.\n"];
            foreach ($errorList as $error) {
                array_push($messageParts['error'], $error[0]);
            }
        }
        if (count($emailList) > 0) {
            //$message['success'] = ["$countAdded users were added as unregistered.\n"];
            $message['success'] = "$countAdded users were added as unregistered.\n";
            array_push($messageParts, $message);
        }
        if ($skipped) {
            $messageParts[] = count($skipped) . " users were skipped because they already exist in the database.\n";
            $messageParts[] = "The skipped emails are: " . implode(', ', $skipped);
        }

        Session::flash('message', $messageParts);

        return back();
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\JsonResponse
     */
    public function getUnregistered(Request $request) {
        $query = User::unregistered();
        if ($request->term) {
            $query->where('email', 'like', "%$request->term%");
        }

        if ($request->country) {
            $query->whereHas('address', function($q) use($request) {
                $q->where('country', $request->country);
            });
        }
        $users = $query->paginate(config('app.pagination'));

        if ($request->ajax()) {
            return response()->json([
                'usersHtml' => View::make('admin.users.unregistered-list', compact('users'))->render(),
                'paginationHtml' => '' . $users->appends($request->all())->render(),
            ]);
        } else {
            return view('admin.users.unregistered', compact('users'));
        }
    }
    public function deleteUnregistered(Request $request) {
        $user = User::unregistered()->findOrFail($request->id);
        $emailU = $user->email;
        $emails = EmailTracking::where('user_id', $user->id)->get();
        if ($emails) {
            foreach ($emails as $email) {
                $email->delete();
            }
        }
        // due to global scope, users first need to be set as registered,
        // otherwise they won't be removed
        $user->registered = true;
        $user->save();
        $user->delete();


        return back()->with('messages.success', "User $emailU was removed");
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
     */
    public function postRegisterUnregisteredForm(Request $request) {
        $user = User::unregistered()->find($request->user_id);
        if (!$user) {
            return back()->with('messages.error', 'User Not Found');
        }

        return view('admin.users.register', compact('user'));
    }

    /**
     * @param Request $request
     * @param Invoicing $invoicing
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postRegisterUnregistered(Request $request, Invoicing $invoicing) {
        $user = User::unregistered()->find($request->id);
        if (!$user) {
            return back()->with('messages.error', 'User Not Found');
        }
        $user->fill($request->except('password'));

        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-";
        $password = substr(str_shuffle($chars), 0, 10);
        $user->business_description = '';
        $user->password = bcrypt($password);
        $user->email_confirmation = md5(rand());
        $user->registered = true;
        $user->registration_token = null;
        $user->save();

        $customer = $invoicing->getCustomers()->where('email', $user->email)->first();
        if ($customer)
            $user->invoice_api_id = $customer->external_id;

        $user->save();

        if (array_filter($request->address)) {
            $address = new \App\Models\User\Address(convert_special_characters($request->address));
            $address->user()->associate($user);
            $address->save();
        }

        $isRegular = !$user->type || $user->type === 'user';
        if ($isRegular && !$user->invoice_api_id) {
            artisan_call_background('users:try-fix-missing-customer');
        }

        return redirect()->route('admin.users.single', ['id' => $user->id])->with('messages.success', "User was successfully registered.\n Password: $password");
    }

    public function postWhatsAppUsersAdded(Request $request) {
        $user = User::findOrFail($request->id);
        $user->whatsapp_added = true;
        $user->save();

        return response()->json([
            'status' => 'success'
        ]);
    }

}
