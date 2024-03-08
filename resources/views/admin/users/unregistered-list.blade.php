<table class="table table-striped" id="users-table">
	<thead>
	<tr>
		<th>Email</th>
		<th>Country</th>
		<th>Register</th>
		<th>Remove</th>
	</tr>
	</thead>
	<tbody>
	@foreach ($users as $user)
		<tr>
			<td>{{ $user->email }}</td>
			<td>{{ $user->address ? $user->address->country : "" }}</td>
			<td>
				{!! BsForm::open(['route' => 'admin.users.register', 'method' => 'post']) !!}
				{!! Form::hidden('user_id', $user->id) !!}
				{!! BsForm::submit("Register",
				['class' => 'btn btn-sm btn-default mb10',
					'data-toggle' => 'tooltip', 'title' => "Register User", 'data-placement'=>'right'
				])
				!!}
				{!! BsForm::close() !!}
			</td>
			<td>
				{!! BsForm::open(['route' => 'admin.users.unregistered-delete', 'method' => 'delete']) !!}
				{!! Form::hidden('id', $user->id) !!}
				{!! BsForm::submit("Delete",
				['class' => 'btn btn-sm btn-default confirmed mb10',
					'data-toggle' => 'tooltip', 'title' => "Delete User", 'data-placement'=>'left',
					'data-confirm' => "Are you sure you want to delete this user?"])
				!!}
				{!! BsForm::close() !!}
			</td>
		</tr>
	@endforeach
	</tbody>
</table>