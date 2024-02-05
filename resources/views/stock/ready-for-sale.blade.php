@extends('app')

@section('title', 'Ready for Sale')

@section('content')

	<div class="container">

		<h2>Ready for Sale</h2>

		@include('messages')

		@include('stock.ready-for-sale-search-form')

		<p class="text-info">Faults: PhoneCheck Report -> Failed</p>

		<div id="universal-table-wrapper">
			@include('stock.ready-for-sale-list')
		</div>
	</div>

@endsection