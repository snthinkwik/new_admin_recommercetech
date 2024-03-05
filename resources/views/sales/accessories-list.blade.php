<table class="table table-bordered table-hover">
	<thead>
	<tr>
		<th style="text-align: center;">Accessories ID</th>
		<th style="text-align: center;">Name</th>
		<th style="text-align: center;">SKU</th>
		<th style="text-align: center;">Quantity</th>
		<th style="text-align: center;">Action</th>
	</tr>
	</thead>
	<tbody>
	@foreach($salesAccessories as $salesAccessory)
		<tr align="center">
			<td>{{ $salesAccessory->id }}</td>
			<td>{{ $salesAccessory->name }}</td>
			<td>{{ $salesAccessory->sku }}</td>
			<td>{{ $salesAccessory->quantity }}</td>
			<td><a href="{{ route('sales.accessories.single', ['id' => $salesAccessory->id]) }}"><i class="fa fa-pencil"></i></a></td>
		</tr>
	@endforeach
	</tbody>
</table>