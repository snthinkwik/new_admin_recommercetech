@extends('app')

@section('title', 'Retail Stock')

@section('content')

	<div class="container">

		<h2>Retail Stock</h2>

		@include('messages')

		{{--<a class="btn btn-default mb15" href="{{ route('channel-grabber.update-logs') }}">CG Update Logs</a>--}}
		{{--<a class="btn btn-default mb15" href="{{ route('back-market.update-logs') }}">Back Market Update Logs</a>--}}

		<br/>

		{{--{!! BsForm::open(['method' => 'post', 'route' => 'back-market.cron-settings', 'class' => 'ib']) !!}
		<div class="form-group">
			<div class="input-group">
				<span class="input-group-addon">BackMarket Sync</span>
			
				
					{!! BsForm::hidden('enabled', 0) !!}
					{!! BsForm::checkbox('enabled', 1, Setting::get('crons.back-market-update-retail-stock-quantities.enabled', true), ['data-toggle' => 'toggle', 'data-onstyle' => 'default']) !!}
			
			
				<span class="input-group-btn">{!! BsForm::submit('Save') !!}</span>
			</div>
		</div>
		{!! BsForm::close() !!}--}}

		@include('stock.retail-stock-search-form')

		<p class="text-info">Faults: PhoneCheck Report -> Failed</p>

		<div id="universal-table-wrapper">
			@include('stock.retail-stock-list')
		</div>
	</div>

@endsection

@section('nav-right')

		<div class="navbar-form navbar-right pr0">
			{!! BsForm::open(['method' => 'post', 'route' => 'stock.update-retail-stock-quantities']) !!}
				{!! BsForm::submit('Push qty to CG', ['class' => 'btn btn-default']) !!}
			{!! BsForm::close() !!}
		</div>

@endsection