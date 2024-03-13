<?php
$days = Request::input('days') ? ['days' => Request::input('days')] : [];
?>
<p>
	Stock Found: {{ money_format($stats->found_purchase_price) }} ({{ $stats->found_count }} items)<br/>
	Total Batch: {{ money_format($stats->batch_purchase_price) }} ({{ $stats->batch_count }} items)<br/>
	Missing: {{ money_format($stats->missing_purchase_price) }} ({{ $stats->missing_count }} items)
</p>

@if(!count($items))
	<div class="alert alert-info">Nothing Found</div>
@else
	<a class="btn btn-default" href="{{ route('stock-take.missing-items-table-all', $days) }}"><i class="fa fa-download"></i> Export all</a>
	<table class="table table-hover">
		<thead>
			<tr>
				<th>Ref</th>
				<th>View Item</th>
				<th>3rd-party-ref</th>
				<th>IMEI</th>
				<th>Name</th>
				<th>Status</th>
				<th>PO Number</th>
				<th>Purchase Value</th>
			</tr>
		</thead>
		<tbody>
			@foreach($items as $item)
				<tr>
					<td>{{ $item->our_ref }}</td>
					<td><a href="{{ route('stock.single', ['id' => $item->id]) }}">View Item</a></td>
					<td>{{ $item->third_party_ref }}</td>
					<td>{{ $item->imei }}</td>
					<td>{{ $item->name }}</td>
					<td>{{ $item->status }}</td>
					<td><a href="{{ route('stock.purchase-order-stats', ['purchase_order_number' => $item->purchase_order_number]) }}">{{ $item->purchase_order_number }}</a></td>
					<td>{{ $item->purchase_price_formatted }}</td>
				</tr>
			@endforeach
		</tbody>
	</table>
@endif
