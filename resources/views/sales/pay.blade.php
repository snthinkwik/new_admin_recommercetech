@extends('app')

@section('title', "Pay for invoice")

@section('content')
	<div class="container">
		<h1>Order payment</h1>
		<div class="row">
			<div class="col-md-12">
				<p><img src="{{ asset('/img/sage-pay.jpg') }}"></p>
				<div class="alert alert-success">
					Please note that the address on your account must the same as where your card is registered to otherwise your payment will fail.
					<br/>
					We currently have the following post code registered for your account: <b>{{ strtoupper(Auth::user()->address->postcode) }}</b>.
					<br/>
					If this is incorrect please click <a href="{{ route('account') }}">here</a> to change it.
				</div>

				@if($sale->invoice_details)
					<div class="alert alert-success">
						Please note that is a charge of 1.6% for card processing payments which will automatically be added to your bill.
					</div>
				@endif
				<iframe id="sage" src="{{ $pendingPayment['sageResponse']->getData()['NextURL'] }}"></iframe>
			</div>
		</div>
	</div>
@endsection