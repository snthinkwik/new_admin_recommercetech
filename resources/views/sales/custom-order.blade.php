<?php
use App\Models\Sale;
$vatTypes = ['' => 'Please Select'] + Sale::getAvailableVatTypesWithKeys();
$readonly = [];
$vatType = null;
if($batch && $price) {
	$vatType = Sale::VAT_TYPE_MARGIN;
	$readonly = ['readonly' => 'readonly'];
}
?>
@extends('app')

@section('title', 'Custom Order')

@section('content')

	<div class="container">

		@include('messages')

		<h2>Custom Order</h2>

		<div class="row">
			{!! BsForm::open(['method' => 'post', 'route' => 'sales.custom-order-create', 'class' => 'summary-batch-form', 'id' => 'custom-order-form']) !!}
			<div class="col-md-4">
				@if($batch)
					{!! BsForm::groupText('batch_id', $batch->id, ['readonly' => 'readonly']) !!}
				@endif
				{!! BsForm::groupText('item_name', $name, ['required' => 'required'] + $readonly) !!}
				<div class="form-group">
					{!! Form::label('amount', 'Amount') !!}
					<div class="input-group">
						<span class="input-group-addon">&pound;</span>
						{!! BsForm::number('amount', $price, ['placeholder' => 'Amount', 'step' => 0.01] + $readonly) !!}
					</div>
				</div>
				<div class="form-group">
					{!! Form::label('vat_type', 'VAT Type') !!}
					{!! BsForm::select('vat_type', $vatTypes, $vatType, ['required' => 'required'] + $readonly) !!}
				</div>
				<div class="input-group">
					<span class="input-group-addon">Customer ID</span>
					{!! Form::number('customer_id', $customerId, ['placeholder' => 'Customer ID', 'min' => 1, 'step' => 1, 'id' => 'summary-customer-load-input', 'class' => 'form-control', 'required' => 'required']) !!}
					<span class="input-group-btn">{!! BsForm::button('Load', ['id' => 'summary-customer-load-button']) !!}</span>
				</div>
				<div class="form-group @hasError('customer_external_id')">
					{!! Form::hidden('customer_external_id', null, ['id' => 'summary-batch-customer-id']) !!}

					@error('customer_external_id') @enderror
				</div>
				{!! Form::submit('Create ' . Auth::user()->texts['sales']['entity'], ['class' => 'btn btn-primary btn-block', 'id' => 'summary-batch-create-sale-button']) !!}
			</div>
			<div class="col-md-8">
				<fieldset id="customer-fieldset" class="mt25" disabled></fieldset>
				{!! Form::hidden('customer_modified', 0) !!}
			</div>
			{!! BsForm::close() !!}
		</div>

	</div>


@endsection
