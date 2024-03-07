<div class="col-md-6">
	<div class="panel panel-default">
		<div class="panel-heading text-center">
			<h4>Devices out on Repair</h4>
		</div>
		<div class="panel-body">
			@if(count($engineersOut))
				<table class="table table-bordered table-hover">
					<thead>
					<tr>
						<th>Name</th>
						<th>No. items</th>
					</tr>
					</thead>
					<tbody>
					@foreach($engineersOut as $engineer)
						<tr>
							<td>{{ $engineer->full_name }}</td>
							<td>{{ $engineer->items_out }}</td>
						</tr>
					@endforeach
					</tbody>
				</table>
			@endif
		</div>
	</div>
	<div class="panel panel-default">
		<div class="panel-heading text-center">
			<h4>Devices awaiting collection</h4>
		</div>
		<div class="panel-body">
			@if(count($devicesAwaiting))
				<table class="table table-bordered table-hover">
					<thead>
					<tr>
						<th>Device</th>
						<th>Engineer</th>
					</tr>
					</thead>
					<tbody>
					@foreach($devicesAwaiting as $device)
						<tr>
							<td>{{ $device->stock->name }}</td>
							<td>{{ $device->engineer->full_name }}</td>
						</tr>
					@endforeach
					</tbody>
				</table>
			@endif
		</div>
	</div>
</div>

<div class="col-md-6">
	<div class="panel panel-default">
		<div class="panel-heading text-center">
			<h4>Number of Repairs complete</h4>
		</div>
		<div class="panel-body">
			@if(count($engineersCompleted))
				<table class="table table-bordered table-hover">
					<thead>
					<tr>
						<th>Engineer Name</th>
						<th>No. items</th>
					</tr>
					</thead>
					<tbody>
					@foreach($engineersCompleted as $engineer)
						<tr>
							<td>{{ $engineer->full_name }}</td>
							<td>{{ $engineer->items_completed }}</td>
						</tr>
					@endforeach
					</tbody>
				</table>
			@endif
		</div>
	</div>
</div>