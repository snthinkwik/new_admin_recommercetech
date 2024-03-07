@extends('app')

@section('title', 'Channel Grabber Update Logs')

@section('content')

	<div class="container">

		<h2>Channel Grabber Update Logs</h2>

		@include('messages')

		<div id="universal-table-wrapper">
			@include('channel-grabber.list')
		</div>

		<div id="universal-pagination-wrapper">
			{!! $logs->appends(Request::all())->render() !!}
		</div>
	</div>

@endsection