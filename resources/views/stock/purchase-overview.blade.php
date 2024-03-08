@extends('app')

@section('title', 'Purchase Overview')

@section('content')

	<div class="container">

		@include('messages')

		<h1>Purchase Overview</h1>

		<table class="table table-striped table-hover">
			<thead>
			<tr>
				<th>Item Name</th>
				<th>No. Items</th>
				<th>Sales Price</th>
				<th>Purchase Price</th>
				<th>Profit</th>
				<th>Items in Stock</th>
				<th>Details</th>
			</tr>
			</thead>
			<tbody>
			@foreach($items as $item)
				<tr>
					<td>{{ $item->name }}</td>
					<td>{{ $item->items }}</td>
					<td>{{ money_format($item->total_sales_price) }}</td>
					<td>{{ money_format($item->total_purchase_price) }}</td>
					<td>{{ money_format($item->profit) }}</td>
					<td>{{ $item->items_to_sell }}</td>
					<td><a class="btn btn-sm btn-default" href="{{ route('stock.purchase-overview-stats', [ 'name' => $item->name ]) }}">Details</a></td>
				</tr>
			@endforeach
			</tbody>
		</table>

	</div>

@endsection
