<?php
use App\Models\Country;
use App\Models\User;
$countries = Country::orderBy('name')->pluck('name', 'name')->toArray();
$customerTypes = ['' => ''] + User::getAvailableCustomerTypesWithKeys();
?>
@extends('app')

@section('title', 'Register Unregistered')

@section('extra-header')
	<script>(function(n,t,i,r){var u,f;n[i]=n[i]||{},n[i].initial={accountCode:"TECHN11191",host:"TECHN11191.pcapredict.com"},n[i].on=n[i].on||function(){(n[i].onq=n[i].onq||[]).push(arguments)},u=t.createElement("script"),u.async=!0,u.src=r,f=t.getElementsByTagName("script")[0],f.parentNode.insertBefore(u,f)})(window,document,"pca","//TECHN11191.pcapredict.com/js/sensor.js")</script>
@endsection

@section('content')
	<div class="container">
		@include('messages')

		<p><a href="{{ route('admin.users.unregistered') }}" class="btn btn-default">Back to Unregistered Users</a></p>

		{!! BsForm::model($user->load('address'), ['route' => 'admin.users.register-save', 'class' => 'form-horizontal']) !!}

			{!! BsForm::hidden('id', $user->id) !!}
			{!! BsForm::hidden('registration_token', $user->registration_token) !!}

		<input type="hidden" name="_token" value="{{ csrf_token() }}">

		<div class="form-group @hasError('first_name')">
			<label class="col-md-4 control-label">First Name</label>
			<div class="col-md-6">
				{!! BsForm::text('first_name', null, ['required']) !!}
				@error('first_name') @enderror
			</div>
		</div>

		<div class="form-group @hasError('last_name')">
			<label class="col-md-4 control-label">Last Name</label>
			<div class="col-md-6">
				{!! BsForm::text('last_name', null, ['required']) !!}
				@error('last_name') @enderror
			</div>
		</div>

		<div class="form-group @hasError('company_name')">
			<label class="col-md-4 control-label">Company Name</label>
			<div class="col-md-6">
				{!! BsForm::text('company_name', null, ['required']) !!}
				@error('company_name') @enderror
			</div>
		</div>

		<div class="form-group @hasError('email')">
			<label class="col-md-4 control-label">Email address</label>
			<div class="col-md-6">
				{!! BsForm::text('email', null, ['disabled']) !!}
				@error('email') @enderror
			</div>
		</div>

		<div class="form-group @hasError('phone')">
			<label class="col-md-4 control-label">Phone Number</label>
			<div class="col-md-6">
				{!! BsForm::text('phone', null, ['required']) !!}
				@error('phone') @enderror
			</div>
		</div>

		<div class="form-group @hasError('business_description')">
			<label class="col-md-4 control-label">Customer Type</label>
			<div class="col-md-6">
				{!! BsForm::select('customer_type', $customerTypes, 'Retail Shop', ['required' => 'required']) !!}
				@error('customer_type') @enderror
			</div>
		</div>

		<div class="form-group @hasError('location')">
			<label class="col-md-4 control-label">Customer location</label>
			<div class="col-md-6">
				{!! BsForm::select('location', ['' => 'Select location...'] + User::getAvailableLocationsWithKeys()) !!}
				@error('location') @enderror
			</div>
		</div>

		<div class="form-group @hasError('address.line1')">
			<label class="col-md-4 control-label">Line 1</label>
			<div class="col-md-6">
				{!! BsForm::text('address[line1]', null, ['required']) !!}
				@error('address.line1') @enderror
			</div>
		</div>

		<div class="form-group @hasError('address.line2')">
			<label class="col-md-4 control-label">Line 2</label>
			<div class="col-md-6">
				{!! BsForm::text('address[line2]') !!}
				@error('address.line2') @enderror
			</div>
		</div>

		<div class="form-group @hasError('address.city')">
			<label class="col-md-4 control-label">City</label>
			<div class="col-md-6">
				{!! BsForm::text('address[city]', null, ['required']) !!}
				@error('address.city') @enderror
			</div>
		</div>

		<div class="form-group @hasError('address.county')">
			<label class="col-md-4 control-label">County</label>
			<div class="col-md-6">
				{!! BsForm::text('address[county]') !!}
				@error('address.county') @enderror
			</div>
		</div>

		<div class="form-group @hasError('address.postcode')">
			<label class="col-md-4 control-label">Postcode</label>
			<div class="col-md-6">
				{!! BsForm::text('address[postcode]', null, ['required']) !!}
				@error('address.postcode') @enderror
			</div>
		</div>

		<div class="form-group mb30 @hasError('address.country')">
			<label class="col-md-4 control-label">Country</label>
			<div class="col-md-6">
				{!! BsForm::select('address[country]', ['' => "Select country..."] + $countries, $user->address ? $user->address->country : "United Kingdom") !!}
				@error('address.country') @enderror
			</div>
		</div>

		<div class="form-group">
			<div class="col-md-6 col-md-offset-4">
				<button type="submit" class="btn btn-primary btn-block">
					Register
				</button>
			</div>
		</div>
		{!! BsForm::close() !!}

	</div>
@endsection
