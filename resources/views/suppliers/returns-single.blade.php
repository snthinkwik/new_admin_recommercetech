<?php
use App\Models\SupplierReturn;
use App\Models\Sale;
$statuses = ['' => 'Please Select'] + SupplierReturn::getAvailableStatusesWithKeys();
$couriers = ['' => 'Please Select'] + Sale::getAvailableCouriersWithKeys();
?>
@extends('app')

@section('title', 'Return Details')

@section('content')

	<div class="container">

		<h2>Supplier Return #{{ $supplierReturn->id }}</h2>

		@include('messages')



		<p><b>Supplier:</b> <a href="{{ route('suppliers.single', ['id' => $supplierReturn->supplier->id]) }}">{{ $supplierReturn->supplier->name }}</a></p>
		<p><b>Status:</b> {{ $supplierReturn->status }}</p>
		<p><b>Total Purchase Value:</b> {{ $supplierReturn->total_purchase_value_formatted }}</p>

		<p><b>Created At:</b> {{ $supplierReturn->created_at->format('d/m/y H:i:s') }}</p>

		@if($supplierReturn->status == SupplierReturn::STATUS_OPEN && $supplierReturn->return_template)
			<a href="{{ route('suppliers.return-single-export', ['id' => $supplierReturn->id]) }}" class="btn btn-sm btn-default">Export Supplier RMA</a>
		@endif
		@if($supplierReturn->supplier->returns_form == 'default.xlsx' && count($supplierReturn->items) > 0)
			<a href="{{ route('suppliers.return-single-export-rma', ['id' => $supplierReturn->id]) }}" class="btn btn-sm btn-default"><i class="fa fa-download"></i> Export RMA</a>
		@endif

		{!! BsForm::model($supplierReturn, ['method' => 'post', 'route' => 'suppliers.return-update-tracking-courier', 'class' => 'mt10 form-inline']) !!}
			{!! BsForm::hidden('id', $supplierReturn->id) !!}

		<div class="row">
					<div class="form-group">
						<div class="input-group">
							<span class="input-group-addon">Tracking Number</span>
							{!! BsForm::text('tracking_number', null, ['required' => 'required']) !!}
						</div>
					</div>
						<div class="form-group">
							<div class="input-group">
								<span class="input-group-addon">Courier</span>
								{!! BsForm::select('courier', $couriers, null, ['required' => 'required']) !!}
							</div>
						</div>
		</div>

		<div class="row  pt-4">
			<div class="form-group ">
				<div class="input-group p-2">


					{{--				{!! BsForm::text('notes',null,['size' => 50,'height'=>50]) !!}--}}
					<textarea type="text" name="note"   class="form-control" style="width: 700px; height: 120px" placeholder="Enter Note">{{$supplierReturn->note}}</textarea>
				</div>
			</div>
		</div>

		<div class="row pt-4">
			{{--			{!! BsForm::groupSubmit('Save') !!}--}}
			<input type="submit" class="btn-success btn btn-block" value="Save">
		</div>


		{!! BsForm::close() !!}

		<hr/>

		{!! BsForm::model($supplierReturn, ['method' => 'post', 'route' => 'suppliers.return-update', 'class' => 'form-inline mt10 mb10']) !!}
			{!! BsForm::hidden('id', $supplierReturn->id) !!}
			<div class="form-group">
				<div class="input-group">
					<span class="input-group-addon">Status</span>
					{!! BsForm::select('status', $statuses, null, ['required' => 'required']) !!}
				</div>
			</div>
			<div class="form-group">
				<div class="input-group">
					<span class="input-group-addon">Notify Supplier?</span>
					{!! BsForm::select('notify', [1 => 'Yes', 0 => 'No'], 1) !!}
				</div>
				{!! BsForm::submit('Save') !!}
			</div>
		{!! BsForm::close() !!}

		<table class="table table-bordered table-hover">
			<tr>
				<th>Ref</th>
				<th>Name</th>
				<th>IMEI</th>
				<th>Third Party</th>
				<th>Purchase Price</th>
				<th>Reason</th>
				<th class="text-center"><i class="fa fa-remove text-danger"></i></th>
			</tr>
			@foreach($supplierReturn->items as $item)

				<tr>
					<td><a href="{{ route('stock.single', ['id' =>!is_null($item->stock) ? $item->stock->id:null]) }}">@if(!is_null($item->stock)) {{ $item->stock->our_ref }} @endif</a></td>
					<td>@if(!is_null($item->stock)) {{ $item->stock->name }}@endif</td>
					<td>@if(!is_null($item->stock)) {{ $item->stock->imei ? : $item->stock->serial }} @endif</td>
					<td>@if(!is_null($item->stock)){{$item->stock->third_party_ref ? $item->stock->third_party_ref:'-'}} @endif</td>
					<td>@if(!is_null($item->stock)){{ $item->stock->purchase_price_no_costs }}@endif</td>

					<td>
						{!! BsForm::model($item, ['method' => 'post', 'route' => 'suppliers.return-update-item']) !!}
							{!! BsForm::hidden('id', $item->id) !!}
							<div class="form-group">
								<div class="input-group">
									{!! BsForm::text('reason') !!}
									<span class="input-group-btn">
										<button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
											<i class="fa fa-check"></i> <span class="caret"></span>
										</button>
										<ul class="dropdown-menu p0">
											<li class="w-100">{!! BsForm::button('This Item', ['type' => 'submit', 'name' => 'action', 'value' => 'item', 'class' => 'btn btn-default btn-block']) !!}</li>
											<li>{!! BsForm::button('All Items', ['type' => 'submit', 'name' => 'action', 'value' => 'items', 'class' => 'btn btn-default btn-block']) !!}</li>
										</ul>
									</span>
								</div>
							</div>
						{!! BsForm::close() !!}
					</td>
					<td>
						{!! BsForm::open(['method' => 'post', 'route' => 'suppliers.return-remove-item']) !!}
							{!! BsForm::hidden('id', $item->id) !!}
							{!! BsForm::button('<i class="fa fa-remove"></i>', ['class' => 'btn btn-sm btn-danger btn-block', 'type' => 'submit']) !!}
						{!! BsForm::close() !!}
					</td>
				</tr>
			@endforeach
		</table>

	</div>

@endsection
