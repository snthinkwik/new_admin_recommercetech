<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Contracts\Invoicing;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class CustomersController extends Controller
{
    public function getDetails(Request $request, Invoicing $invoicing)
    {
        $customer = $invoicing->getCustomer($request->external_id);

        $formHtml = View::make(
            'customers.form',
            compact('customer', 'invoicing') + ['showWarning' => true]
        )->render();

        return response()->json([
            'customer' => $customer,
            'formHtml' => strip_form($formHtml, 'customer'),
        ]);
    }

    public function postSave(Request $request, Invoicing $invoicing)
    {
        $customer = $invoicing->getCustomer($request->external_id);
        $customer->fill($request->all());
        $customer->billing_address = new Address(convert_special_characters($request->billing_address));
        $customer->shipping_address = new Address(convert_special_characters($request['shipping_address']));
        $invoicing->updateCustomer($customer);
        $user = User::findOrFail($request->user_id);
        $user->address->fill($request->billing_address);
        $user->address->save();

        return back()->with('messages.success', 'Customer Data updated');
    }

}
