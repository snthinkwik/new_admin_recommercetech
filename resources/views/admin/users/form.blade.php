<?php

use App\Models\User;

$customerTypes = ['' => ''] + User::getAvailableCustomerTypesWithKeys();
$availableLocations = ['' => ''] + User::getAvailableLocationsWithKeys();
$categoryList = ['' => 'Please Select Customer Category'] + \App\Invoicing\Quickbooks::getAvailableCustomerCategoryWithKeys();
$ty = \App\Models\Supplier::get()->pluck('name', 'id');

$suppliers = ['' => 'None'] + $ty->toArray();

//$categoryList=[];
//$quickBooksModel=\App\QuickbooksProductService::get();
//foreach ($quickBooksModel as $cat){
//
//    $categoryList[$cat->value]=$cat->name;
//
//}


//dd($categoryList);
?>
{!! Form::model($user, ['route' => 'admin.users.save', 'id' => 'user-form']) !!}
@if ($user->exists)
    {!! Form::hidden('id') !!}
@endif


<div class="form-group @hasError('email')">
    <label for="user-email">Email</label>
    {!! Form::text('email', null, ['class' => 'form-control', 'id' => 'user-email']) !!}
    @error('email') @enderror
</div>

<div class="form-group @hasError('first_name')">
    <label for="user-first-name">First Name</label>
    {!! Form::text('first_name', null, ['class' => 'form-control', 'id' => 'user-first-name']) !!}
    @error('first_name') @enderror
</div>

<div class="form-group @hasError('last_name')">
    <label for="user-last-name">First Name</label>
    {!! Form::text('last_name', null, ['class' => 'form-control', 'id' => 'user-last-name']) !!}
    @error('last_name') @enderror
</div>

<div class="form-group @hasError('password')">
    <label for="user-password">Password @if ($user->exists) (leave empty for unchanged) @endif</label>
    {!! Form::password('password', ['class' => 'form-control', 'id' => 'user-password']) !!}
    @error('password') @enderror
</div>
<div class="form-group @hasError('invoice_api_id')">
    <label for="sale-customer">
        {{ $invoicing->getSystemName() }} Customer
    </label>
    <p>Current Customer ID: {{ $user->invoice_api_id }}</p>
    <div class="input-group">
        <span class="input-group-addon">Customer ID</span>
        {!! BsForm::text('user_invoice_api_id', $user->invoice_api_id) !!}
    </div>
    @error('invoice_api_id') @enderror
</div>

{!! BsForm::groupSelect('customer_type', $customerTypes) !!}
{!! BsForm::groupSelect('quickbooks_customer_category', $categoryList,null,['required' => 'required']) !!}

{!! BsForm::groupSelect('location', $availableLocations) !!}

<div class="form-group">
    {!! Form::label('user-phone', 'Phone Number') !!}
    {!! BsForm::text('phone') !!}
</div>

<div class="form-group">
    {!! Form::label('company_name', 'Company Name') !!}
    {!! BsForm::text('company_name') !!}
</div>

<div class="form-group">
    {!! Form::label('user-balance', 'Balance') !!}
    <span id="customer-balance">
			<a class="btn btn-sm btn-default" id="customer-balance-button">Get balance</a>
		</span>
</div>
<div class="form-group">
    {!! Form::label('balance-in-db', "Balance in DB") !!}
    {!! BsForm::text('balance_spent', $user->balance_spent) !!}
</div>

<div class="form-group">
    {!! BsForm::groupSelect('vat_types',['0'=>'All types','1'=>'Marginal','2'=>'Vatable'],null,['required' => 'required']) !!}
</div>

<div class="form-group">
    {!! BsForm::groupSelect('supplier_id', $suppliers) !!}
</div>
<div class="form-group">
    {!! Form::label('user-spend', 'Total Spend') !!}
    <span>
			{{ money_format($total_spend) }}

		</span>
</div>

<div class="form-group">
    <div class="input-group">
        <span class="input-group-addon"><i class="fa fa-whatsapp"></i> What's App</span>
        {!! BsForm::hidden('whatsapp', 0) !!}
        {!! BsForm::checkbox('whatsapp', 1, $user->whatsapp, ['data-toggle' => 'toggle', 'class' => 'toggle-yes-no']) !!}
    </div>
</div>

<div class="form-group">
    {!! Form::label('user-spend', 'Sell To Recomm') !!}
    <label>

        {!! BsForm::hidden('id', $user->id) !!}
        {!! BsForm::hidden('sell_to_recomm', 0) !!}
        {!! BsForm::checkbox('sell_to_recomm', 1, $user->sell_to_recomm, ['data-toggle' => 'toggle','class' => 'toggle-yes-no']) !!}

    </label>
</div>

<div class="form-group">
    {!! Form::label('user-spend', 'Terms and conditions signed?') !!}
    <label>

        {!! BsForm::hidden('id', $user->id) !!}
        {!! BsForm::hidden('received', 0) !!}
        {!! BsForm::checkbox('received', 1, $user->received, ['data-toggle' => 'toggle','class' => 'toggle-yes-no']) !!}

    </label>
</div>

<div class="form-group">
    {!! Form::label('user-spend', 'KYC verification?') !!}
    <label>

        {!! BsForm::hidden('id', $user->id) !!}
        {!! BsForm::hidden('kyc_verification', 0) !!}
        {!! BsForm::checkbox('kyc_verification', 1, $user->kyc_verification, ['data-toggle' => 'toggle','class' => 'toggle-yes-no']) !!}

    </label>
</div>

<div class="form-group">
    {!! Form::label('processing_data', 'Processing Data?') !!}
    <label>

        {!! BsForm::hidden('id', $user->id) !!}
        {!! BsForm::hidden('processing_data', 0) !!}
        {!! BsForm::checkbox('processing_data', 1, $user->processing_data, ['data-toggle' => 'toggle','class' => 'toggle-yes-no']) !!}

    </label>
</div>

<div class="form-group">
    {!! Form::label('processing_price', 'Processing Price?') !!}
    <label>

        {!! BsForm::hidden('id', $user->id) !!}
        {!! BsForm::hidden('processing_price', 0) !!}
        {!! BsForm::checkbox('processing_price', 1, $user->processing_price, ['data-toggle' => 'toggle','class' => 'toggle-yes-no']) !!}

    </label>
</div>

<div class="form-group">
    {!! Form::label('purchase_from_us', ' Purchase from Us?') !!}
    <label>

        {!! BsForm::hidden('id', $user->id) !!}
        {!! BsForm::hidden('purchase_from_us', 0) !!}
        {!! BsForm::checkbox('purchase_from_us', 1, $user->purchase_from_us, ['data-toggle' => 'toggle','class' => 'toggle-yes-no']) !!}

    </label>
</div>

<div class="form-group">
    <label for="user-notes">Notes</label>
    {!! BsForm::textarea('notes', $user->notes, ['rows' => 3]) !!}
</div>

<div class="form-group">
    <label for="user-heard-about-us">How did you hear about us?</label>
    {!! BsForm::text('heard_about_us', $user->heard_about_us, ['disabled' => 'disabled']) !!}
</div>

{!! BsForm::groupSubmit('Save', ['class' => 'btn btn-primary']) !!}
{!! Form::close() !!}

<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <form method="post" action="{{route('admin.user.quick_books.product.add')}}" >
        <input type="hidden" name="_token" value="{{{ csrf_token() }}}"/>
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Add Product Or Service For QuickBooks</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <label>Title</label>
                    <input type="text" class="form-control" name="name"><br>
                    <label>Value</label>
                    <input type="text" class="form-control" name="value">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add</button>
                </div>
            </div>
        </div>
    </form>
</div>
