<?php
use App\Unlock\Order;
?>
@extends('app')

@section('title', "Unlock order details")

@section('content')

	<div class="container">
		@include('messages')

		<p class="small"><a href="{{ route('unlocks.own-stock') }}">Back to list of orders</a></p>

		<h1>Unlock order details</h1>
		<table class="table table-striped">
			<tbody>
				<tr><th>Status</th><td>{{ $order->status }}</td></tr>
				<tr><th>Network</th><td>{{ $order->network }}</td></tr>
				<tr><th>Models</th><td>{{ $order->models }}</td></tr>
				<tr><th>IMEIs</th><td>{{ implode(', ', $order->imeis) }}</td></tr>
				<tr><th>Amount</th><td>{{ $order->amount_formatted }}</td></tr>
			</tbody>
		</table>
		<div class="row">
		@if ($order->status === Order::STATUS_NEW)
			<div class="col-md-2">
				<a href="{{ route('unlocks.pay-submit', ['id' => $order->id]) }}" class="btn btn-info btn-block">Pay</a>
			</div>
			<div class="col-md-2">
				{!!
					BsForm::open([
						'route' => 'unlocks.own-stock.order-cancel',
						'onsubmit' => 'return confirm("Are you sure you want to cancel this order?")'
					])
				!!}
				{!! BsForm::hidden('id', $order->id) !!}
				{!! BsForm::submit('Cancel', ['class' => 'btn btn-danger btn-block']) !!}
				{!! BsForm::close() !!}
			</div>
		@endif
		@if ($order->invoice_creation_status === 'success')
			<div class="col-md-2">
				<a href="{{ route('unlocks.invoice', $order->id) }}" class="btn btn-block btn-default" target="blank">
					Invoice #{{ $order->invoice_number }}
				</a>
			</div>
		@endif
		</div>
		@if ($order->unlocks)
			<h2>IMEI details</h2>
			<table class="table table-striped">
				<thead>
					<tr>
						<th>IMEI</th>
						<th>Status</th>
					</tr>
				</thead>
				<tbody>
					@foreach ($order->unlocks as $unlock)
						<tr>
							<td>{{ $unlock->imei }}</td>
							<td>{{ $unlock->status }}</td>
						</tr>
					@endforeach
				</tbody>
			</table>
		@endif
	</div>

@endsection