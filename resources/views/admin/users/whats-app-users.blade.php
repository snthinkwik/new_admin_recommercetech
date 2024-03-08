@extends('app')

@section('title', 'Users')

@section('content')

	<div class="container">

		@include('messages')

		<p>
			<a href="{{ route('admin.users.new-user') }}">Add new</a> |
			<a href="{{ route('admin.users.bulk-add-form') }}">Bulk add Unregistered</a> |
			<a href="{{ route('admin.users.unregistered') }}">Search Unregistered</a> |
			<a href="{{ route('admin.users.whats-app-users') }}">What's App Users</a>
		</p>

		<h2>What's App Users</h2>

		@if(!count($users))
			<div class="alert alert-info">Nothing to Display</div>
		@else
			<div id="whatsapp-users-wrapper">
				<p><b>Users Added:</b> <span class="users-added">0</span></p>
				<table class="table table-hover">
					<tr>
						<th>Name</th>
						<th>Customer ID</th>
						<th>Company Name</th>
						<th>Phone Number</th>
						<th>Added?</th>
					</tr>
					@foreach($users as $user)
						<tr>
							<td><a href="{{ route('admin.users.single', ['id' => $user->id]) }}" target="_blank">{{ $user->full_name }}</a></td>
							<td>{{ $user->invoice_api_id }}</td>
							<td>{{ $user->company_name }}</td>
							<td>{{ $user->phone }}</td>
							<td><a class="remove-from-list btn btn-sm added btn-primary" data-user-id="{{ $user->id }}"><i class="fa fa-check"></i> Added</a></td>
						</tr>
					@endforeach
				</table>
			</div>
		@endif

	</div>

@endsection