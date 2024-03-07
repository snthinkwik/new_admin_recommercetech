@extends('app')

@section('title', 'Batches')

@section('content')

	<div class="container">

		<h2>Batches</h2>

		<a class="btn btn-default mb10" href="{{ route('batches.new-custom') }}"><i class="fa fa-plus"></i> New Custom Batch</a>

		@include('messages')

		@include('batches.search-form')

		<a class="btn btn-default ib" href="{{ route('batches.summary') }}">Batches Summary</a>

		@if(count($batches))
			{!! BsForm::open(['id' => 'batches-merge-form', 'class' => 'form-inline ib', 'method' => 'post', 'route' => 'batches.merge']) !!}
			<div class="input-group">
				<span class="input-group-addon">Batch 1</span>
				{!! BsForm::select('batch_1', $batches->lists('id', 'id')) !!}
				<span class="input-group-addon">Batch 2</span>
				{!! BsForm::select('batch_2', $batches->lists('id', 'id')) !!}
				<div class="input-group-btn">
					{!! BsForm::submit("Merge",
						['class' => 'confirmed',
						'data-toggle' => 'tooltip', 'title' => "Merge Batches", 'data-placement'=>'right',
						'data-confirm' => "Are you sure you want to merge these two batches? Items from first batch will go to second batch."])
					!!}
				</div>
			</div>
			{!! BsForm::close() !!}

			<a class="btn btn-default batches-select-all-button">Check All</a>
			<a href="javascript:" class="btn btn-default batches-email-send-button">Send Batches Email</a>
		@endif

		<div id="universal-table-wrapper">
			@include('batches.list')
		</div>

		<div id="universal-pagination-wrapper">
			{!! $batches->appends(Request::all())->render() !!}
		</div>

	</div>

@endsection
