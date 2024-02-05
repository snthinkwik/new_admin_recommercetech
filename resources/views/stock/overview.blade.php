@extends('app')

@section('title', 'Stock overview')

@section('content')

	<div class="container-fluid">
		<div class='universal-loader' style="display: none" ><div class='universal-spinner'></div></div>

		@include('messages')
		@include('stock.nav')

		@if (!count($stock))
			<div class="alert alert-warning">Nothing to show.</div>
		@else


			@include('stock.search-form-inventory')


			<div id="stock-items-wrapper">
				@include('stock.overview-items')
			</div>
		@endif

		<div class="" id="stock-pagination-wrapper" style="margin-left: 10px;">{!! $stock->render() !!}</div>
	</div>

@endsection

@section('nav-right')
	@if (Auth::user()->type !== 'user' && in_array(Auth::user()->admin_type, ['admin', 'manager']))
		<div class="navbar-form navbar-right pr0">
			<div class="btn-group">
				<div class="d-flex justify-content-end">
					<a href="{{route('inventory.export.csv')}}" class="p-5 btn btn-success">Export CSV</a>
				</div>
			</div>
		</div>
	@endif




@endsection
