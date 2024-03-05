<?php
use Illuminate\Support\Facades\Request;
use App\Country;
use App\User;
$filters = ['' => 'All', 'never_bought' => 'Never Bought', 'not_last_45_days' => 'Not 45 Days'];
//$countries = Country::orderBy('name')->lists('name');
//$countries = ['' => 'All'] + array_combine($countries, $countries);
//$customerTypes = ['' => ''] + User::getAvailableCustomerTypesWithKeys();
?>
{!! BsForm::open(['id' => 'user-search-form', 'class' => 'form-inline mb15', 'method' => 'get']) !!}
<div class="form-group">
{{--    {!! BsForm::text('term', Request::input('term'), ['id' => 'user-search-term', 'placeholder' => 'Search (Name, Email, Phone)', 'size' => 30]) !!}--}}
{{--    {!! BsForm::groupSelect('filter', $filters, Request::input('filter'), ['id' => 'user-search-filter']) !!}--}}
{{--    {!! BsForm::groupSelect('country', $countries) !!}--}}
{{--    {!! BsForm::groupSelect('customer_type', $customerTypes, Request::input('customer_type')) !!}--}}
</div>
{!! BsForm::close() !!}
