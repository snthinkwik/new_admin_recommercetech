@extends('app')

@section('title', 'Engineer Report')

@section('content')

	<div class="container">

		@include('messages')

		<h1>Engineer Report</h1>
		<small>Taken daily at 5:30pm</small>
		<table class="table table-striped table-hover">
			<thead>
				<tr>
					<th>Date</th>
					<th>User</th>
					<th>No of Items</th>
				</tr>
			</thead>
			<tbody>
			@foreach($reports as $report)
				<tr>
					<td>{{ $report->created_at->format("d-m-Y") }}</td>
					<td>{{ $report->user ? $report->user->full_name : ''}}</td>
					<td>{{ $report->items_count }}</td>
				</tr>
			@endforeach
			</tbody>
		</table>

	</div>

@endsection