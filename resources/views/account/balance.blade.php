@extends('app')

@section('title', 'My balance')

@section('content')

	<div class="container">
		<h2>My balance</h2>
		<p class="lead">
			Your account balance is <strong>{{ Auth::user()->balance_formatted }}</strong>.
		</p>
		<p>
			<a href="{{ route('sales') }}">Click here</a> to see your orders.
		</p>
	</div>

@endsection