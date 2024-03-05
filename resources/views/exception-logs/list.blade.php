@if(!count($logs))
	<div class="alert alert-info">Nothing Found</div>
@else
	<table class="table table-bordered">
		<tr>
			<th>ID</th>
			<th><i class="fa fa-user"></i></th>
			<th>Method</th>
			<th>URL</th>
			<th>Command</th>
			<th>Exception (short)</th>
			<th><i class="fa fa-calendar"></i></th>
			<th>Details</th>
		</tr>
		@foreach($logs as $log)
			<tr>
				<td>{{ $log->id }}</td>
				<td>@if($log->user) <a href="{{ route('admin.users.single', ['id' => $log->user->id]) }}">{{ $log->user->first_name }}</a> @endif</td>
				<td>{{ $log->method }}</td>
				<td>{{ $log->url }}</td>
				<td>{{ $log->command }}</td>
				<td>{{ $log->exception_short }}</td>
				<td>{{ $log->created_at->format('d/m/y H:i:s') }}</td>
				<td><a class="btn btn-default btn-block" href="{{ route('exception-logs.single', ['id' => $log->id]) }}">Details</a></td>
			</tr>
		@endforeach
	</table>
@endif