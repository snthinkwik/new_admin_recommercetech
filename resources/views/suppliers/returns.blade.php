<?php
use App\Models\Supplier;
use App\Models\SupplierReturn;
$suppliers = ['' => '-'] + Supplier::get()->pluck('name', 'id')->toArray();
$statuses = ['Open' => 'Open'] + SupplierReturn::getAvailableStatusesWithKeys();
?>
@extends('app')

@section('title', 'Supplier Returns')

@section('content')

	<div class="container">

		@include('messages')

		<h2>Supplier Returns</h2>

		{!! BsForm::open(['id' => 'universal-search-form', 'class' => 'form-inline mt5 mb15', 'method' => 'get']) !!}
		<div class="form-group">
			<div class="input-group">
				<span class="input-group-addon">
					Status
				</span>
				{!! BsForm::select('status', $statuses, Request::input('status')) !!}
			</div>
			<div class="input-group">
				<span class="input-group-addon">
					Supplier
				</span>
				{!! BsForm::select('supplier_id', $suppliers, Request::input('supplier_id')) !!}
			</div>
		</div>
		{!! BsForm::close() !!}

		<div class="row">
			<div class="col-md-12">
				<div id="universal-table-wrapper">
					@include('suppliers.returns-list')
				</div>
				<div id="universal-pagination-wrapper">
					{!! $supplierReturns->appends(Request::All())->render() !!}
				</div>
			</div>
		</div>

	</div>

@endsection
