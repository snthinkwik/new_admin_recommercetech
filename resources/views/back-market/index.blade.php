@extends('app')

@section('title', 'Back Market Update Logs')

@section('content')

	<div class="container">

		<h2>Back Market Update Logs</h2>

		@include('messages')

		<div id="universal-table-wrapper">
			@include('back-market.list')
		</div>

		<div id="universal-pagination-wrapper">
			{!! $logs->appends(Request::all())->render() !!}
		</div>
	</div>

@endsection