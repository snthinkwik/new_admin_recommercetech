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
//$invoicing = app('App\Contracts\Invoicing');
?>
@extends('app')

@section('title', 'Confirm Sale to Other Recycler')

@section('content')

	<div class="container">
		@include('messages')

		<h3>Other Recycler Sale Smmary</h3>
		<h4>Amount: £{{ number_format($amount, 2) }}</h4>
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
		{!! Form::open(['route' => 'sales.save-other', 'id' => 'sale-summary-other-form', 'class' => 'mb15']) !!}
		<table class="table table-striped">
			<thead>
			<tr>
				<th>RCT Ref</th>
				<th>Name</th>
				<th>Capacity</th>
				<th>Colour</th>
				<th>Grade</th>
				<th>Network</th>
				@if (Auth::user()->type !== 'user')
					<th>Purchase price</th>
				@endif
				<th>Price</th>
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
					<td>{{ $item->name }}</td>
					<td>{{ $item->capacity_formatted }}</td>
					<td>{{ $item->colour }}</td>
					<td>{{ $item->grade }}</td>
					<td>{{ $item->network }}</td>
					@if (Auth::user()->type !== 'user')
						<td>{{ $item->purchase_price_formatted }}</td>
					@endif
					<td>
						@if (!empty($request->items[$item->id]['price']))
							£{{ number_format($request->items[$item->id]['price'], 2) }}
						@else
							{{ $item->sale_price_formatted }}
						@endif
					</td>
				</tr>
			@endforeach
			</tbody>
		</table>

		<div class="row form-group">
			<div class="col-md-4">
				{!! Form::label('recycler', 'Recycler') !!}
				{!! BsForm::select('recycler', ['Cex' => 'CeX', 'Music Magpie' => 'Music Magpie', 'Envirofone' => 'Envirofone', 'eBay' => 'eBay','Other' => 'Other']) !!}
			</div>
			<div class="col-md-4">
				{!! Form::label('other_recycler', "Recycler->Other (won't work if not Other selected)") !!}
				{!! BsForm::text('other_recycler', null, ['placeholder' => 'Other Recycler']) !!}
			</div>
			<div class="col-md-4">
				{!! Form::label('recyclers_order_number', 'Recyclers Order Number') !!}
				{!! BsForm::text('recyclers_order_number', null, ['placeholder' => 'Recyclers Order Number', 'required']) !!}
			</div>
			<div class="col-md-4" id="account-name">
				{!! Form::label('account_name', 'Account Name') !!}
				{!! BsForm::text('account_name', null, ['placeholder' => 'Account Name']) !!}
			</div>
		</div>
		<div class="row">
			<div class="col-md-6 col-md-offset-3">
				{!! Form::hidden('amount', $amount) !!}
				{!! Form::submit('Create ' . Auth::user()->texts['sales']['entity'], ['class' => 'btn btn-block btn-primary']) !!}
			</div>
		</div>

		{!! Form::close() !!}
	</div>

@endsection

@section('nav-right')
	<div id="basket-wrapper" class="navbar-right pr0">
		@include('basket.navbar')
	</div>
@endsection