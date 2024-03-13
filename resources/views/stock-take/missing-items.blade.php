@extends('app')

@section('title', 'Missing Items')

@section('content')

	<div class="container">

		<h2>Missing Items <i class="fa fa-question-circle" data-toggle="tooltip" data-trigger="click hover focus" data-placement="right bottom" data-container="body" title="Missing items are ones that have not been scanned as shown in 7, if a stock take has been done then these items should be considered lost"></i> </h2>

		<p>Total Missing Value: {{ money_format($totalMissingValue) }} (never scanned)</p>

		{!! BsForm::open(['id' => 'universal-search-form', 'class' => 'form-inline mb10 mt10']) !!}
		<div class="form-group">
			<div class="input-group">
				<span class="input-group-addon">Days</span>
				{!! BsForm::number('days', Request::input('days') ? : 7, ['min' => 1]) !!}
			</div>
		</div>
		{!! BsForm::close() !!}

		<div id="universal-table-wrapper">
			@include('stock-take.missing-items-list')
		</div>

		<div id="universal-pagination-wrapper">
			{!! $items->appends(Request::all())->render() !!}
		</div>
	</div>

@endsection
