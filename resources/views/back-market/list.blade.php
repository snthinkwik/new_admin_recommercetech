@if(!count($logs))
	<div class="alert alert-info">Nothing Found</div>
@else
	<table class="table table-bordered table-hover">
		<tr>
			<th>Date</th>
			<th>Cron</th>
			<th>SKU Qty</th>
			<th>Found</th>
			<th>Not Found</th>
			<th>Updated</th>
			<th>Update Error</th>
			<th>More Details</th>
		</tr>
		@foreach($logs as $log)
			<tr>
				<td>{{ $log->created_at->format('d/m/y H:i:s') }}</td>
				<td>{{ $log->cron }}</td>
				<td>{{ $log->sku_qty }}</td>
				<td>{{ $log->found_qty }}</td>
				<td>{{ $log->not_found_qty }}</td>
				<td>{{ $log->updated_qty }}</td>
				<td>{{ $log->update_error_qty }}</td>
				<td><a class="btn btn-default btn-sm btn-block" href="{{ route('back-market.update-logs-single', ['id' => $log->id]) }}">More Details</a></td>
			</tr>
		@endforeach
	</table>
@endif	