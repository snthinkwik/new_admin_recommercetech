@if(!count($unlockMappings))
	<div class="alert alert-info">Nothing Found</div>
@else
	<table class="table table-striped table-hover">
		<thead>
			<tr>
				<th>Network</th>
				<th>Click2Unlock Service ID</th>
				<th>Make</th>
				<th>Model</th>
				<th>Cost</th>
				<th>Delete</th>
			</tr>
		</thead>
		<tbody>
		@foreach($unlockMappings as $unlockMapping)
			<tr>
				<td>{{ $unlockMapping->network }}</td>
				<td>{{ $unlockMapping->service_id }}</td>
				<td>{{ $unlockMapping->make }}</td>
				<td>{{ $unlockMapping->model }}</td>
				<td> @if($unlockMapping->cost != 0.00) {{$unlockMapping->cost_formatted}} @endif</td>
				<td>
					{!! BsForm::open(['method' => 'post', 'route' => 'unlock-mapping.delete']) !!}
						{!! BsForm::hidden('id', $unlockMapping->id) !!}
						{!! BsForm::button('Delete', ['type' => 'submit', 'class' => 'btn btn-danger btn-xs btn-block']) !!}
					{!! BsForm::close() !!}
				</td>
			</tr>
		@endforeach
		</tbody>
	</table>
@endif