<?php
use App\Unlock\Order;
?>
@extends('app')

@section('title', "Unlock your own stock")

@section('content')

	<div class="container">
		@include('messages')

		<h1>Unlock your own stock</h1>

		<div class="mb15">
			<a href="{{ route('unlocks.own-stock.new-order') }}">New order</a>
		</div>

		@if (count($orders))
			<table class="table striped">
				<caption>Your orders</caption>
				<thead>
					<tr>
						<th>Status</th>
						<th>Network</th>
						<th>Models</th>
						<th>IMEIs</th>
						<th>Amount</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					@foreach ($orders as $order)
						<tr>
							<td>{{ $order->status }}</td>
							<td>{{ $order->network }}</td>
							<td>{{ $order->models }}</td>
							<td>{{ implode(', ', $order->imeis) }}</td>
							<td>{{ $order->amount_formatted }}</td>
							<td>
								@if ($order->status === Order::STATUS_NEW)
									<a href="{{ route('unlocks.pay-submit', ['id' => $order->id]) }}" class="btn btn-xs btn-info btn-block">Pay</a>
									{!!
										BsForm::open([
											'route' => 'unlocks.own-stock.order-cancel',
											'onsubmit' => 'return confirm("Are you sure you want to cancel this order?")'
										])
									!!}
										{!! BsForm::hidden('id', $order->id) !!}
										{!! BsForm::submit('Cancel', ['class' => 'btn btn-danger btn-xs btn-block']) !!}
									{!! BsForm::close() !!}
								@endif
								<a href="{{ route('unlocks.own-stock.order-details', $order->id) }}" class="btn btn-xs btn-block btn-default">Details</a>
							</td>
						</tr>
					@endforeach
				</tbody>
			</table>
		@endif
	</div>

@endsection