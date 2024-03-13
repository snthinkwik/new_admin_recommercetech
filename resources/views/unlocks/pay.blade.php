@extends('app')

@section('title', "Pay for order")

@section('content')
	<div class="container">
		<h1>Order payment</h1>
		<p><img src="{{ asset('/img/sage-pay.jpg') }}"></p>
		<iframe id="sage" src="{{ $pendingPayment['sageResponse']->getData()['NextURL'] }}"></iframe>
	</div>
@endsection