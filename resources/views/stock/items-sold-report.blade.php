@extends('app')

@section('title', 'Items Sold Report')

@section('content')

	<div class="container">
		<h2>Items Sold Report</h2>
		<table class="table table-striped table-hover">
			<thead>
				<tr>
					<th>Month</th>
					<th>Items Sold</th>
					<th>Purchase Price Total</th>
					<th>Sales Price Total</th>
					<th>Profit</th>
				</tr>
			</thead>
			<tbody>
				@foreach($months as $month)
					<tr>
						<td>{{ $month['month'] }}</td>
						<td>{{ $month['items_sold'] }}</td>
						<td>{{ money_format(config('app.money_format'), $month['purchase_price']) }}</td>
						<td>{{ money_format(config('app.money_format'), $month['sales_price']) }}</td>
						<td>{{ money_format(config('app.money_format'), $month['profit']) }}</td>
					</tr>
				@endforeach
			</tbody>
		</table>
	</div>

@endsection