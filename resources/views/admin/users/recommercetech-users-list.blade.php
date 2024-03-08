<?php
use App\User;
use App\PhoneCheck;
$types = ['' => ' - '] + User::getAvailableAdminTypesWithKeys();
$stations = ['' => ' - '] + PhoneCheck::getAvailableStatationIds()
?>
@if(!count($users))
	<div class="alert alert-info">Nothing Found</div>
@else
	<table class="table table-bordered table-hover">
		<tr>
			<th>ID</th>
			<th>User</th>
			<th>Email</th>
			<th>Admin Type</th>
			<th>Station ID</th>
			<th>Delete</th>
		</tr>
		@foreach($users as $user)
			<?php if(!in_array($user->station_id, array_keys($stations))) { $stations[$user->station_id] = $user->station_id; } ?>
			<tr>
				<td><a href="{{ route('admin.users.single', ['id' => $user->id]) }}">{{ $user->id }}</a></td>
				<td>{{ $user->full_name }}</td>
				<td>{{ $user->email }}</td>
				<td>
					{!! BsForm::open(['method' => 'post', 'route' => 'admin.users.update-admin-type', 'class' => '']) !!}
						<div class="input-group input-group-sm">
							{!! BsForm::hidden('id', $user->id) !!}
							{!! BsForm::select('admin_type', $types, $user->admin_type, ['required' => 'required']) !!}
							<span class="input-group-btn">{!! BsForm::button('<i class="fa fa-check"></i>', ['type' => 'submit', 'class' => 'btn btn-xs btn-success']) !!}</span>
						</div>
					{!! BsForm::close() !!}
				</td>
				<td>
					{!! BsForm::open(['method' => 'post', 'route' => 'admin.users.update-station-id', 'class' => '']) !!}
					<div class="input-group input-group-sm">
						{!! BsForm::hidden('id', $user->id) !!}
						{!! BsForm::select('station_id', $stations, $user->station_id) !!}
						<span class="input-group-btn">{!! BsForm::button('<i class="fa fa-check"></i>', ['type' => 'submit', 'class' => 'btn btn-xs btn-success']) !!}</span>
					</div>
					{!! BsForm::close() !!}
				</td>
				<td>
					{!! BsForm::open(['method' => 'post', 'route' => 'admin.users.delete-admin']) !!}
						{!! BsForm::hidden('id', $user->id) !!}
						{!! BsForm::submit('Delete', ['class' => 'btn btn-sm btn-block btn-danger confirmed','data-confirm' => 'Are you sure you want to remove this user?']) !!}
					{!! BsForm::close() !!}
				</td>
			</tr>
		@endforeach
	</table>
@endif