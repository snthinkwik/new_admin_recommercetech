@extends('app')

@section('title', 'Purchase Overview')

@section('content')

	<div class="container">

		<a class="btn btn-default" href="{{ route('stock.purchase-overview') }}">Back to Purchase Overview</a>
		<h1>Purchase Overview - {{ $stats['name'] }}</h1>

		<table class="table">
			<tbody>
			<tr class="active"><th>No. Devices</th><td>{{ $stats['total'] }}</td></tr>

			<tr data-toggle="collapse" data-target="#items-sold" class="info-light"><th>No. Items Sold</th><td>{{ count($items_sold) }}</td></tr>
			<tr data-toggle="collapse" data-target="#items-to-sell" class="info-light"><th>No. Items To Sell</th><td>{{ count($items_to_sell) }}</td></tr>

			<tr class="success-light"><th>% fully working - no touch id</th><td>{{ number_format($stats['fully_working_no_touch_id'] / $stats['total'] * 100) }}%</td></tr>
			<tr class="success-light"><th>% fully working</th><td>{{ number_format($stats['fully_working'] / $stats['total'] * 100) }}%</td></tr>
			<tr class="success-light"><th>% minor fault</th><td>{{ number_format($stats['minor_fault'] / $stats['total'] * 100) }}%</td></tr>
			<tr class="success-light"><th>% major fault</th><td>{{ number_format($stats['major_fault'] / $stats['total'] * 100) }}%</td></tr>
			<tr class="success-light"><th>% no signs of life</th><td>{{ number_format($stats['broken'] / $stats['total'] * 100) }}%</td></tr>

			<tr class="info-light"><th>% grade A</th><td>{{ number_format($stats['condition_a'] / $stats['total'] * 100) }}%</td></tr>
			<tr class="info-light"><th>% grade B</th><td>{{ number_format($stats['condition_b'] / $stats['total'] * 100) }}%</td></tr>
			<tr class="info-light"><th>% grade C</th><td>{{ number_format($stats['condition_c'] / $stats['total'] * 100) }}%</td></tr>

			<tr class="success-light"><th>% unlocked</th><td>{{ number_format($stats['networks']['unlocked'] / $stats['total'] * 100) }}%</td></tr>
			<tr class="success-light"><th>% unknown</th><td>{{ number_format($stats['networks']['unknown'] / $stats['total'] * 100) }}%</td></tr>
			<tr class="success-light"><th>% Vodafone</th><td>{{ number_format($stats['networks']['vodafone'] / $stats['total'] * 100) }}%</td></tr>
			<tr class="success-light"><th>% EE</th><td>{{ number_format($stats['networks']['ee'] / $stats['total'] * 100) }}%</td></tr>
			<tr class="success-light"><th>% Three</th><td>{{ number_format($stats['networks']['three'] / $stats['total'] * 100) }}%</td></tr>
			<tr class="success-light"><th>% O2</th><td>{{ number_format($stats['networks']['o2'] / $stats['total'] * 100) }}%</td></tr>
			<tr class="success-light"><th>% other</th><td>{{ number_format($stats['networks']['other'] / $stats['total'] * 100) }}%</td></tr>

			<tr class="info-light"><th>Total Purchase Price</th><td>£{{ number_format($stats['purchase_price']) }}</td></tr>
			<tr class="info-light"><th>Total Sales Price</th><td>£{{ number_format($stats['sales_price']) }}</td></tr>
			<tr class="info-light"><th>Gross Profit</th><td>£{{ number_format($stats['gross_profit']) }}</td></tr>
			</tbody>
		</table>


		<div class="panel panel-default">
			<div data-toggle="collapse" data-target="#items-sold" class="panel-heading">Items Sold <span class="badge">{{ count($items_sold) }}</span></div>
			<div class="panel-body collapse" id="items-sold">
				<table class="table table-condensed table-small">
					<thead>
					<tr>
						<th>Ref</th>
						<th>Sku</th>
						<th>3rd-party ref</th>
						<th>Name</th>
						<th>Capacity</th>
						<th>Colour</th>
						<th>Condition</th>
						<th>Grade</th>
						<th>Network</th>
						<th>Status</th>
						<th>Sales price</th>
						<th>Purchase date</th>
						<th>Purchase price</th>
					</tr>
					</thead>
					<tbody>
					@foreach ($items_sold as $item)
						<tr>
							<td>{{ $item->our_ref }}</td>
							<td>
								<a href="{{ route('stock.single', ['id' => $item->id]) }}">
									{{ $item->sku }}
								</a>
							</td>
							<td>{{ $item->third_party_ref }}</td>
							<td>{{ $item->name }}</td>
							<td>{{ $item->capacity_formatted }}</td>
							<td>{{ $item->colour }}</td>
							<td>{{ $item->condition }}</td>
							<td>{{ $item->grade }}</td>
							<td>{{ $item->network }}</td>
							<td>{{ $item->status }}</td>
							<td>{{ $item->sale_price_formatted }}</td>
							<td>{{ $item->purchase_date ? $item->purchase_date->format('Y-m-d') : '' }}</td>
							<td>{{ $item->purchase_price_formatted }}</td>
						</tr>
					@endforeach
					</tbody>
				</table>
			</div>
		</div>

		<div class="panel panel-default">
			<div data-toggle="collapse" data-target="#items-to-sell" class="panel-heading">Items To Sell <span class="badge">{{ count($items_to_sell) }}</span></div>
			<div class="panel-body collapse" id="items-to-sell">
				<table class="table table-condensed table-small">
					<thead>
					<tr>
						<th>Ref</th>
						<th>Sku</th>
						<th>3rd-party ref</th>
						<th>Name</th>
						<th>Capacity</th>
						<th>Colour</th>
						<th>Condition</th>
						<th>Grade</th>
						<th>Network</th>
						<th>Status</th>
						<th>Sales price</th>
						<th>Purchase date</th>
						<th>Purchase price</th>
					</tr>
					</thead>
					<tbody>
					@foreach ($items_to_sell as $item)
						<tr>
							<td>{{ $item->our_ref }}</td>
							<td>
								<a href="{{ route('stock.single', ['id' => $item->id]) }}">
									{{ $item->sku }}
								</a>
							</td>
							<td>{{ $item->third_party_ref }}</td>
							<td>{{ $item->name }}</td>
							<td>{{ $item->capacity_formatted }}</td>
							<td>{{ $item->colour }}</td>
							<td>{{ $item->condition }}</td>
							<td>{{ $item->grade }}</td>
							<td>{{ $item->network }}</td>
							<td>{{ $item->status }}</td>
							<td>{{ $item->sale_price_formatted }}</td>
							<td>{{ $item->purchase_date ? $item->purchase_date->format('Y-m-d') : '' }}</td>
							<td>{{ $item->purchase_price_formatted }}</td>
						</tr>
					@endforeach
					</tbody>
				</table>
			</div>
		</div>
	</div>

@endsection