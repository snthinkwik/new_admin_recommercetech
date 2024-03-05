<?php
use App\Sale;
use Illuminate\Support\Facades\Request;
$hasAvailabilityError = false;
foreach (Request::input('items') as $id => $itemData) {
	if ($errors->has('items.' . $id . '.price') || $errors->has('items.' . $id . '.status')) {
		$hasAvailabilityError = true;
		break;
	}
}
$invoicing = app('App\Contracts\Invoicing');
?>
@extends('app')

@section('title', 'Confirm ' . Auth::user()->texts['sales']['entity'])

@section('content')

	<div class="container">
		@include('messages')

		<h3>{{ ucfirst(Auth::user()->texts['sales']['entity']) }} summary</h3>
		<h2>Batch #{{ $batch }} - £{{ $price }} - {{ count($stock) }} items</h2>
		@if(isset($auction) && $user)
			<h3>Auction #{{ $auction->id }} - Top bid User - <a href="{{ route('admin.users.single', ['id' => $user->id ]) }}">#{{ $user->id  }} {{ $user->full_name }}</a></h3>
			<p id="auctionUser" class="hidden">{{ $user->invoice_api_id }}</p>
		@endif
		<a class="btn btn-primary" data-toggle="collapse" data-target="#itemsTable">Show Items</a>
		@if ($errors->has('order_amount'))
			<div class="alert alert-danger">
				Your order value is below the MOQ of {{ money_format(config('app.money_format'), Sale::MINIMUM_ORDER_AMOUNT) }} -
				please add more items to check out
			</div>
		@endif
		@if ($hasAvailabilityError)
			<div class="alert alert-danger">
				It seems that some prices or availability of some items has changed since you started creating the order. You can try
				<a class="alert-link" href="{{ Illuminate\Support\Facades\Request::fullUrl() }}">refreshing this page</a> or
				going back to the <a class="alert-link" href="{{ route('stock') }}">stock page</a>.
			</div>
		@endif
		@if (count($stock) !== count($request->items))
			<p class="text-warning">Some of the items you requested are not shown because they're not available for sale.</p>
		@endif
		{!! Form::open(['route' => 'sales.save-batch', 'id' => 'sale-summary-form', 'class' => 'mb15 summary-batch-form']) !!}
		{!! Form::hidden('price', $price) !!}
		{!! Form::hidden('batch', $batch) !!}
		@if(isset($auction))
			{!! Form::hidden('auction', $auction->id) !!}
		@endif
		<div class="panel panel-default collapse" id="itemsTable">
		<table class="table table-striped">
			<thead>
			<tr>
				<th>RCT Ref</th>
				<th>Name</th>
				<th>Capacity</th>
				<th>Colour</th>
				<th>Grade</th>
				<th>Network</th>
				<th>Purchase date</th>
				@if (Auth::user()->type !== 'user')
					<th>Purchase price</th>
				@endif
			</tr>
			</thead>
			<tbody>
			@foreach ($stock as $item)
				<tr>
					<td>
						{!!
							Form::hidden(
								'items[' . $item->id . '][price]',
								isset($request->items[$item->id]['price']) ? $request->items[$item->id]['price'] : $item->sale_price,
								['class' => 'form-control', 'placeholder' => 'Price']
							)
						!!}
						{{ $item->our_ref }}
					</td>
					<td><a target="_blank" href="{{ route('stock.single', ['id' => $item->id]) }}">{{ $item->name }}</a></td>
					<td>{{ $item->capacity_formatted }}</td>
					<td>{{ $item->colour }}</td>
					<td>{{ $item->grade }}</td>
					<td>{{ $item->network }}</td>
					<td>{{ $item->purchase_date }}</td>
					@if (Auth::user()->type !== 'user')
						<td>{{ $item->total_costs_formatted }}</td>
					@endif
				</tr>
			@endforeach
			</tbody>
		</table>
		</div>
		@if (Auth::user()->type !== 'user')
			{!! BsForm::groupCheckbox('customer_is_collecting') !!}
			<div class="row">
				<div class="col-md-4">
					<div class="input-group">
						<span class="input-group-addon">Customer ID</span>
						{!! Form::number('customer_id', $request->customer_id ? : null, ['placeholder' => 'Customer ID', 'min' => 1, 'step' => 1, 'id' => 'summary-customer-load-input', 'class' => 'form-control', 'required' => 'required']) !!}
						<span class="input-group-btn">{!! BsForm::button('Load', ['id' => 'summary-customer-load-button']) !!}</span>
					</div>
					<div class="form-group @hasError('customer_external_id')">
						{{--<label for="sale-customer">Customer</label>--}}
						{!! Form::hidden('customer_external_id', null, ['id' => 'summary-batch-customer-id']) !!}
						{{--{!!
							Form::text(
								'customer_external_name',
								null,
								['class' => 'form-control customer-field click-select-all', 'placeholder' => 'Search or select', 'id' => 'summary-batch-customer']
							)
						!!}--}}
						<p class="text-muted small">
							If you can't find an existing {{ $invoicing->getSystemName() }} customer, please
							<a target="_blank" href="{{ route('admin.users') }}">add them</a> first.
						</p>
						@error('customer_external_id')
					</div>
				</div>
				<div class="col-md-8">
					<fieldset id="customer-fieldset" class="mt25"></fieldset>
					{!! Form::hidden('customer_modified', 0) !!}
				</div>
			</div>
		@endif
		{!! Form::button('Create ' . Auth::user()->texts['sales']['entity'], ['class' => 'btn btn-primary', 'id' => 'summary-batch-create-sale-button']) !!}
		{!! Form::close() !!}
	</div>

@endsection

@section('pre-scripts')
	{{--<script>
		Data.sales.customers = {!! json_encode($customersForAutocomplete) !!};
	</script>--}}
@endsection

@section('nav-right')
	<div id="basket-wrapper" class="navbar-right pr0">
		@include('basket.navbar')
	</div>
@endsection