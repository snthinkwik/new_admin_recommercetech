<?php
use App\Models\User;
use App\Models\Country;
$countries = Country::orderBy('name')->pluck('name')->toArray();
$heardAboutUs = ['' => 'Please Select'] + User::getAvailableHeardAboutUsWithKeys();
?>
@extends('app')

@section('title', 'New User')

@section('content')

	<div class="container">
		<h2>Add New User</h2>
		@include('messages')

		{!! Form::model($user, ['route' => 'admin.users.new-user-create', 'id' => 'user-form']) !!}
		@if ($user->exists)
			{!! Form::hidden('id') !!}
		@endif

		<div class="form-group">
			<label for="user-type">Type</label>
			{!!
				Form::select(
					'type', ['user' => 'User', 'manager' => 'Manager', 'admin' => 'Admin'], null, ['class' => 'form-control']
				)
			!!}
		</div>

		<div class="form-group @hasError('email')">
			<label for="user-email">Email</label>
			{!! BsForm::text('email', null, ['required' => 'required']) !!}
			@error('email') @enderror
		</div>

		<div class="form-group @hasError('first_name')">
			<label for="user-first-name">First name</label>
			{!! BsForm::text('first_name', null, ['required' => 'required']) !!}
			@error('first_name') @enderror
		</div>

		<div class="form-group @hasError('last_name')">
			<label for="user-last-name">Last name</label>
			{!! BsForm::text('last_name', null, ['required' => 'required']) !!}
			@error('last_name') @enderror
		</div>

		<div class="form-group @hasError('password')">
			<label for="user-password">Password @if ($user->exists) (leave empty for unchanged) @endif</label>
			{!! Form::password('password', ['class' => 'form-control', 'id' => 'user-password']) !!}
			@error('password') @enderror
		</div>

		<div class="form-group @hasError('company_name')">
			{!! Form::label('company_name') !!}
			{!! BsForm::text('company_name', null, ['required' => 'required']) !!}
			@error('company_name') @enderror
		</div>

		<div class="form-group @hasError('business_description')">
			{!! Form::label('business_description') !!}
			{!! BsForm::textarea('business_description', null, ['rows' => 3]) !!}
			@error('business_description') @enderror
		</div>

		<div class="form-group @hasError('phone')">
			{!! Form::label('phone') !!}
			{!! BsForm::text('phone', null, ['required' => 'required']) !!}
			@error('phone') @enderror
		</div>


		<div class="form-group @hasError('location')">
			<label>Customer location*</label>
			{!! BsForm::select('location', ['' => 'Select location...'] + User::getAvailableLocationsWithKeys(), null, ['required' => 'required']) !!}
			@error('location') @enderror
		</div>


		<div class="row">
			<div class="col-lg-6">

				<fieldset>
					<legend>Billing Address:
						<small style="margin-left:367px;font-size: 10px;font-weight: bold"><span style="position: absolute;margin-left: -204px;
    margin-top: 9px;">Same address copy for Shipping Address</span> <input type="checkbox" id="copy_shipping"> </small>
					</legend>
					<div class="form-group @hasError('address.country')">
						<label>Country*</label>
						{!! BsForm::select('billing_address[country]', ['' => "Select country..."] + array_combine($countries, $countries), 'United Kingdom', ['required' => 'required','id'=>'billing_address_country']) !!}
						@error('billing_address.country') @enderror
					</div>

					<div class="form-group @hasError('billing_address.line1')">
						<label>Line 1*</label>
						{!! BsForm::text('billing_address[line1]', null, ['required' => 'required','id'=>'billing_address_line1']) !!}
						@error('billing_address.line1') @enderror
					</div>

					<div class="form-group @hasError('billing_address.line2')">
						<label>Line 2</label>
						{!! BsForm::text('billing_address[line2]',null,['id'=>'billing_address_line2']) !!}
						@error('billing_address.line2') @enderror
					</div>

					<div class="form-group @hasError('billing_address.city')">
						<label>City*</label>
						{!! BsForm::text('billing_address[city]', null, ['required' => 'required','id'=>'billing_address_city']) !!}
						@error('billing_address.city') @enderror
					</div>

					<div class="form-group @hasError('billing_address.county')">
						<label>County*</label>
						{!! BsForm::text('billing_address[county]', null, ['required' => 'required','id'=>'billing_address_county']) !!}
						@error('billing_address.county') @enderror
					</div>

					<div class="form-group @hasError('billing_address.postcode')">
						<label>Postcode*</label>
						{!! BsForm::text('billing_address[postcode]', null, ['required' => 'required','id'=>'billing_address_postcode']) !!}
						@error('address.postcode') @enderror
					</div>

				</fieldset>

			</div>
			<div class="col-lg-6">

				<fieldset>
					<legend>Shipping Address <small style="margin-left:367px;font-size: 10px;font-weight: bold"><span style="position: absolute;margin-left: -190px;
    margin-top: 9px;">Same address copy for Billing Address</span> <input type="checkbox" id="copy_billing"> </small></legend>

					<div class="form-group @hasError('address.country')">
						<label>Country*</label>
						{!! BsForm::select('address[country]', ['' => "Select country..."] + array_combine($countries, $countries), 'United Kingdom', ['required' => 'required','id'=>'address_country']) !!}
						@error('address.country') @enderror

					</div>

					<div class="form-group @hasError('address.line1')">
						<label>Line 1*</label>
						{!! BsForm::text('address[line1]', null, ['required' => 'required','id'=>'address_line1']) !!}
						@error('address.line1') @enderror
					</div>

					<div class="form-group @hasError('address.line2')">
						<label>Line 2</label>
						{!! BsForm::text('address[line2]',null,['id'=>'address_line2']) !!}
						@error('address.line2') @enderror
					</div>

					<div class="form-group @hasError('address.city')">
						<label>City*</label>
						{!! BsForm::text('address[city]', null, ['required' => 'required','id'=>'address_city']) !!}
						@error('address.city') @enderror
					</div>

					<div class="form-group @hasError('address.county')">
						<label>County*</label>
						{!! BsForm::text('address[county]', null, ['required' => 'required','id'=>'address_county']) !!}
						@error('address.county') @enderror
					</div>

					<div class="form-group @hasError('address.postcode')">
						<label>Postcode*</label>
						{!! BsForm::text('address[postcode]', null, ['required' => 'required','id'=>'address_postcode']) !!}
						@error('address.postcode') @enderror
					</div>

				</fieldset>


			</div>


		</div>
		<div class="form-group @hasError('whatsapp')">
			<label>Add to What's App distribution list?*</label>
			<div>
				<label class="radio-inline">
					{!! Form::radio("whatsapp", 1, old() ? null : true) !!} Yes
				</label>
				<label class="radio-inline">
					{!! Form::radio("whatsapp", 0) !!} No
				</label>
			</div>
			@error('whatsapp') @enderror
		</div>


		<div class="form-group @hasError('vat_registered')">
			<label>VAT registered?*</label>
			<div>
				<label class="radio-inline">
					{!! Form::radio("vat_registered", 1) !!} Yes
				</label>
				<label class="radio-inline">
					{!! Form::radio("vat_registered", 0) !!} No
				</label>
			</div>
			@error('vat_registered') @enderror
		</div>

		<div class="form-group @hasError('devices_per_week') mb30">
			<label>How many devices does the user usually purchase a week?</label>
			{!! BsForm::text('devices_per_week') !!}
			@error('devices_per_week') @enderror
		</div>

		<div class="form-group @hasError('heard_about_us')">
			<label>How did user hear about us?</label>
			{!! BsForm::select('heard_about_us', $heardAboutUs, null, ['required' => 'required']) !!}
			@error('heard_about_us') @enderror
		</div>

		{!! BsForm::groupSubmit('Save', ['class' => 'btn btn-primary']) !!}
		{!! Form::close() !!}

	</div>

@endsection


@section('pre-scripts')
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
	<script>
		$("#copy_billing").click(function () {

           var checked= $("#copy_billing").is(":checked");


			var address_country=$("#address_country").val();
            var address_line1=$("#address_line1").val();
            var address_line2=$("#address_line2").val();
            var address_city=$("#address_city").val();
            var address_county=$("#address_county").val();
            var address_postcode=$("#address_postcode").val();

            if(checked){
                $("#billing_address_country").val(address_country);
                $("#billing_address_line1").val(address_line1);
                $("#billing_address_line2").val(address_line2);
                $("#billing_address_city").val(address_city);
                $("#billing_address_county").val(address_county);
                $("#billing_address_postcode").val(address_postcode)
			}else{

                $("#billing_address_country").val('');
                $("#billing_address_line1").val('');
                $("#billing_address_line2").val('');
                $("#billing_address_city").val('');
                $("#billing_address_county").val('');
                $("#billing_address_postcode").val('')
			}



        })

        $("#copy_shipping").click(function () {

            var checked= $("#copy_shipping").is(":checked");


            var address_country=$("#billing_address_country").val();
            var address_line1=$("#billing_address_line1").val();
            var address_line2=$("#billing_address_line2").val();
            var address_city=$("#billing_address_city").val();
            var address_county=$("#billing_address_county").val();
            var address_postcode=$("#billing_address_postcode").val();

            if(checked){
                $("#address_country").val(address_country);
                $("#address_line1").val(address_line1);
                $("#address_line2").val(address_line2);
                $("#address_city").val(address_city);
                $("#address_county").val(address_county);
                $("#address_postcode").val(address_postcode)
            }else{

                $("#address_country").val('');
                $("#address_line1").val('');
                $("#address_line2").val('');
                $("#address_city").val('');
                $("#address_county").val('');
                $("#address_postcode").val('')
            }



        })

	</script>
	@endsection
