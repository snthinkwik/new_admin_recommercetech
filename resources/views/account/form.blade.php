<?php
use App\Models\User;
use App\Models\Country;
$countries = Country::orderBy('name')->get();
$countriesForSelect = ['' => "Please select"] + array_combine($countries->pluck('name')->toArray(), $countries->pluck('name')->toArray());

?>
{!! BsForm::model($user->load('address'), [], ['id-prefix' => 'account']) !!}
	{!! BsForm::groupText('first_name', null, ['disabled']) !!}
	{!! BsForm::groupText('last_name', null, ['disabled']) !!}
	{!! BsForm::groupText('phone') !!}
	{!! BsForm::groupText('email') !!}
	{!! BsForm::groupSelect('location', User::getAvailableLocationsWithKeys()) !!}
	{!! BsForm::groupText('address[line1]', null, null, ['label' => 'Billing address: Line 1']) !!}
	{!! BsForm::groupText('address[line2]', null, null, ['label' => 'Billing address: Line 2']) !!}
	{!! BsForm::groupText('address[city]', null, null, ['label' => 'Billing address: City']) !!}
	{!! BsForm::groupSelect('address[country]', $countriesForSelect, null, null, ['label' => 'Billing address: Country']) !!}
	{!! BsForm::groupText('address[postcode]', null, null, ['label' => 'Billing address: Postcode']) !!}
    @if($customer)
        {!! BsForm::groupText('shipping_address[line1]', ($customer->shipping_address ? $customer->shipping_address->line1 : null), null, ['label' => 'Shipping address: Line 1']) !!}
        {!! BsForm::groupText('shipping_address[line2]', ($customer->shipping_address ? $customer->shipping_address->line2 : null), null, ['label' => 'Shipping address: Line 2']) !!}
        {!! BsForm::groupText('shipping_address[city]', ($customer->shipping_address ? $customer->shipping_address->city : null), null, ['label' => 'Shipping address: City']) !!}
        {!! BsForm::groupSelect('shipping_address[country]', $countriesForSelect, ($customer->shipping_address ? $customer->shipping_address->country : null), null, ['label' => 'Shipping address: Country']) !!}
        {!! BsForm::groupText('shipping_address[postcode]', ($customer->shipping_address ? $customer->shipping_address->postcode : null), null, ['label' => 'Shipping address: Postcode']) !!}
    @endif
	{!! BsForm::groupSubmit('Save') !!}
{!! BsForm::close() !!}
