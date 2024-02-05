@extends('app')

@section('title', 'Exception Logs - Details')

@section('content')

	<div class="container">

		<a class="btn btn-default" href="{{ route('exception-logs') }}"><i class="fa fa-reply"></i> Back to list</a>

		<h2>Exception Logs - Details</h2>

		@include('messages')

		<div class="row">
			<div class="col-md-4">
				<div class="panel panel-default">
					<div class="panel-heading">Info</div>
					<div class="panel-body">
						<table class="table table-bordered table-hover">
							<tr>
								<th>Date</th>
								<td>{{ $log->created_at->format('d/m/y H:i:s') }}</td>
							</tr>
							<tr>
								<th>User</th>
								<td>@if($log->user) <a href="{{ route('admin.users.single', ['id' => $log->user->id]) }}">{{ $log->user->full_name }}</a> @endif</td>
							</tr>
							<tr>
								<th>Method</th>
								<td>{{ $log->method }}</td>
							</tr>
							<tr>
								<th>URL</th>
								<td>{{ $log->url }}</td>
							</tr>
							<tr>
								<th>Command</th>
								<td>{{ $log->command }}</td>
							</tr>
						</table>
					</div>
				</div>
			</div>
			<div class="col-md-8">
				<div class="panel panel-default">
					<div class="panel-heading">Details</div>
					<div class="panel-body">
						{!! $log->exception_short !!}
						<hr/>
						<span class="word-break-all">{!! $log->exception_long !!}</span>
					</div>
				</div>
			</div>
		</div>

	</div>

@endsection