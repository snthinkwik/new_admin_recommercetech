<?php

namespace App\Http\Controllers;

use App\Contracts\Invoicing;
use App\Http\Requests\UserFullUpdateRequest;
use App\Models\Sale;
use App\Models\User;
use App\Models\User\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AccountController extends Controller
{
    public function getIndex(Invoicing $invoicing)
    {
        $user = Auth::user();
        $customer = $user->invoice_api_id ? $invoicing->getCustomer($user->invoice_api_id) : null;
        $unpaid = $user->invoice_api_id ? Sale::where('customer_api_id', $user->invoice_api_id)->where('invoice_status', 'open')->count() : null;

        return view('account.index', compact('user', 'customer', 'unpaid'));
    }

    public function postApiGenerateKey()
    {
        $user = Auth::user();
        $user->api_key = md5(rand()) . md5(rand());
        $user->save();
        return back()->with('messages.success', "Your API key has been set.");
    }

    public function getApi()
    {
        return view('account.api');
    }

    public function postIndex(UserFullUpdateRequest $request, Invoicing $invoicing)
    {
        $user = Auth::user();
        $user->fill($request->only(['email', 'phone', 'location']));
        $user->save();
        $address = $user->address;
        if(!$address){
            $address = new Address();
            $address->user()->associate($user);
        }
        $address->fill(convert_special_characters($request->address));
        $address->save();
//		$invoicing->updateCustomer($user->getCustomer());

        if($user->invoice_api_id) {
            $customer = $user->getCustomer();
            $customer->billing_address = $address;
            $customer->shipping_address = new Address(convert_special_characters($request['shipping_address']));
            $customer->fill($request->all());
            $invoicing->updateCustomer($customer);
        }

        if (Session::get('account.country.save_redirect')) {
            $redirect = Session::remove('account.country.save_redirect');
            $res = redirect($redirect);
        }
        else {
            $res = back();
        }

        $res->with('messages.success', "Your account has been updated.");
        return $res;
    }

    public function postChangePassword(Request $request)
    {
        $user = Auth::user();
        if(!Hash::check($request->current_password, $user->password)) {
            return back()->with('messages.error', 'Wrong current password');
        }
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|min:1',
            'password' => 'required|min:6|same:password_confirmation',
            'password_confirmation' => 'required|min:6|same:password'
        ]);

        if($validator->fails()) {
            return back()->withErrors($validator->errors());
        }
        $user->password = bcrypt($request->password);
        $user->save();

        return back()->with('messages.success', 'Password has been updated.');
    }

    public function getDisableNotifications(Request $request)
    {
        $user = $request->token
            ? User::withUnregistered()->where('id', $request->id)->where('registration_token', $request->token)->first()
            : null;
        return view('account.disable-notifications', compact('user'));
    }

    public function postDisableNotifications(Request $request)
    {
        $user = $request->token
            ? User::withUnregistered()->where('id', $request->id)->where('registration_token', $request->token)->first()
            : null;
        if ($user) {
            $user->marketing_emails_subscribe = false;
            $user->whatsapp = false;
            $user->save();
            return back()->with('messages.success', "You have been removed from our mailing list.")
                ->with('account.just_unsubscribed', true);
        }
        else {
            return back()->with('messages.error', "Something went wrong. Please contact us.");
        }
    }

    public function getRegisteredDisableNotifications(Request $request)
    {
        $user = ($request->id && $request->email) ? User::where('id', $request->id)->where('email', $request->email)->first() : null;

        return view('account.registered-disable-notifications', compact('user'));
    }

    public function postRegisteredDisableNotifications(Request $request)
    {
        $user = ($request->id && $request->email) ? User::where('id', $request->id)->where('email', $request->email)->first() : null;

        if($user) {
            $user->marketing_emails_subscribe = false;
            $user->whatsapp = false;
            $user->save();
            return back()->with('messages.success', "You have been removed from our mailing list.")
                ->with('account.just_unsubscribed', true);
        } else {
            return back()->with('messages.error', "Something went wrong. Please contact us.");
        }
    }

    public function getSettings()
    {
        return view('account.settings', ['user' => Auth::user()]);
    }

    public function postSettings(Request $request)
    {
        $user = Auth::user();
        Auth::user()->fill($request->only(['marketing_emails_subscribe']));
        $user->save();

        if(!$user->marketing_emails_subscribe && $user->whatsapp) {
            $user->whatsapp = 0;
            $user->save();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Account settings have been saved',
        ]);
    }

    public function getBalance()
    {
        return view('account.balance');
    }

}
