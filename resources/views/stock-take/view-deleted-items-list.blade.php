@if(!count($items))
	<div class="alert alert-info">Nothing Found</div>
@else
	<table class="table table-hover table-bordered">
		<thead>
		<tr>
			<th>RCT Ref</th>
			<th>Make</th>
			<th>Model</th>
			<th>Colour</th>
			<th>IMEI</th>
		</tr>
		</thead>
		<tbody>
		@foreach($items as $item)
			<tr>
				<td><a href="{{ route('stock.single', ['id' => $item->id]) }}">{{ $item->our_ref }}</a></td>
				<td>{{ $item->make }}</td>
				<td>{{ $item->name }}</td>
				<td>{{ $item->colour }}</td>
				<td>{{ $item->imei }}</td>
			</tr>
		@endforeach
		</tbody>
	</table>
@endif