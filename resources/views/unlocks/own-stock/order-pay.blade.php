@extends('app')

@section('title', "Unlock order payment")

@section('content')

	<div class="container">
		@include('messages')
		<p class="small"><a href="{{ route('unlocks.own-stock') }}">Back to list of orders</a></p>

		<h1>Your order</h1>
		<table class="table table-striped">
			<tbody>
				<tr><th>Network</th><td>{{ $order->network }}</td></tr>
				<tr><th>Models</th><td>{{ $order->models }}</td></tr>
				<tr><th>IMEIs</th><td>{{ implode(', ', $order->imeis_awaiting_payment) }}</td></tr>
				<tr><th>Amount</th><td>{{ $order->amount_formatted }}</td></tr>
			</tbody>
		</table>
		{!! BsForm::open(['route' => 'unlocks.own-stock.order-pay']) !!}
			{!! BsForm::hidden('order_id', $order->id) !!}
			<script
				src="https://checkout.stripe.com/checkout.js" class="stripe-button"
				data-key="{{ config('services.stripe.publishable_key') }}"
				data-amount="{{ $order->amount * 100 }}"
				data-name="{{ config('app.name') }}"
				data-description="Unlock order payment"
				data-locale="auto"
				data-currency="gbp"
				data-email="{{ Auth::user()->email }}"
				data-allow-remember-me="false"
				data-label="Pay with Stripe"
			>
			</script>
		{!! BsForm::close() !!}
	</div>

@endsection