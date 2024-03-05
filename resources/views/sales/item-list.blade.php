<table class="table table-striped table-condensed">
	<thead>
	<tr>
		<th>Ref</th>
		<th>Device Name</th>
		<th>Capacity</th>
		<th>Network</th>
		<th>IMEI</th>
		<th>Serial</th>
		<th>Sales Price</th>
		<th>Return this item</th>
		<th>Est Net Profit</th>
	</tr>
	</thead>
	<tbody>
	@foreach ($sale->stock as $item)
		<tr>
			<td>{{ $item->our_ref }}</td>
			<td>{{ $item->name }}</td>
			<td>{{ $item->capacity_formatted }}</td>
			<td>{{ $item->network }}</td>
			<td>{{ $item->imei }}</td>
			<td>{{ $item->serial }}</td>
			<td>{{ $item->sale_price_formatted }}</td>
			<td>Est Net Profit</td>
		</tr>
	@endforeach
	</tbody>
</table>