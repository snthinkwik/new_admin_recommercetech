@extends('app')

@section('title', 'Deleted Items')

@section('content')

	<div class="container">

		<h2>Deleted Items</h2>

		@include('stock-take.view-deleted-items-list')

		{!! $items->render() !!}
	</div>

@endsection