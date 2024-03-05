@extends('app')

@section('title', 'Exception Logs')

@section('content')

	<div class="container">

		<h2>Exception Logs</h2>

		@include('messages')

		{!! BsForm::open(['id' => 'universal-search-form', 'class' => 'mb15']) !!}
			<div class="form-group">
				<div class="input-group">
					<span class="input-group-addon">Search</span>
					{!! BsForm::text('term', Request::input('term')) !!}
				</div>
			</div>
		{!! BsForm::close() !!}

		<div id="universal-table-wrapper">
			@include('exception-logs.list')
		</div>

		<div id="universal-pagination-wrapper">
			{!! $logs->appends(Request::all())->render() !!}
		</div>
	</div>

@endsection