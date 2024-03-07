<?php
use Carbon\Carbon;
?>
@extends('app')

@section('title', "Repairs - Details")

@section('content')

	<div class="container">

		<h2>Internal Repair #{{ $repair->id }} - Details</h2>

		<a class="btn btn-default mb10" href="{{ route('repairs') }}"><i class="fa fa-reply"></i> Back to list</a>



		@include('messages')

		<div class="row">
			<div class="col-md-6">
				<div class="panel panel-default">
					<div class="panel-heading">Details</div>
					<div class="panel-body">
						<table class="table table-bordered table-hover">
							<tr>
								<th>ID</th>
								<td>{{ $repair->id }}</td>
							</tr>
							<tr>
								<th>Status</th>
								<td>{{ $repair->repairstatus->name }}</td>
							</tr>
							<tr>
								<th>Engineer</th>
								<td>{{ $repair->repairengineer->name }}</td>
							</tr>
							<tr>
								<th>Stock ID</th>
								<td>{{ $repair->item_id }}</td>
							</tr>
							<tr>
								<th>Item Name</th>
								<td>{{ $repair->stock->name }}</td>
							</tr>
							<tr>
								<th>Item Status</th>
								<td>{{ $repair->stock->status }}</td>
							</tr>
							<tr>
								<th>No. Days In Repair</th>
								<td>{{ $repair->closed_at ? $repair->created_at->diffInDays($repair->closed_at) : $repair->created_at->diffInDays(Carbon::now()) }}</td>
							</tr>
							<tr>
								<th>Created At</th>
								<td>{{ $repair->created_at->format('d/m/y H:i:s') }}</td>
							</tr>
							<tr>
								<th>Closed At</th>
								<td>{{ $repair->closed_at ? $repair->closed_at->format('d/m/y H:i:s') : '-' }}</td>
							</tr>
						</table>
					</div>
				</div>
			</div>
			<div class="col-md-6">
				<div class="panel panel-default">
					<div class="panel-heading">Parts</div>
					<div class="panel-body">
						{{ $repair->parts }}
					</div>
				</div>
			</div>
		</div>

	</div>

@endsection