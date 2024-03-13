@if(!count($customers))
	<div class="alert alert-info">Nothing found</div>
@else
	<table class="table table-bordered table-hover">
		<tr>
			<th></th>
			<th>Customer ID</th>
			<th>User</th>
			<th>Email</th>
			<th>Balance Due Date</th>
			<th>Phone</th>
			<th>Balance</th>
			<th>Undispatched Orders</th>
			<th>Hidden?</th>
		</tr>
		@foreach($customers as $customer)
			<tr>
				<td>{!! Form::checkbox('users['.$customer->id.']', $customer->balance_due, 0) !!}</td>
				<td>{{ $customer->invoice_api_id }}</td>
				<td><a href="{{ route('admin.users.single', ['id' => $customer->id]) }}">{{ $customer->full_name }}</a></td>
				<td>{{ $customer->email }}</td>
				<td>
					{!! BsForm::open(['method' => 'post', 'route' => 'admin.users.update-balance-due-date']) !!}
					{!! BsForm::hidden('id', $customer->id) !!}
					<div class="input-group input-group-sm">
						{!! BsForm::text('balance_due_date', $customer->balance_due_date != '-0001-11-30 00:00:00' ? $customer->balance_due_date : '', ['class' => 'has-datepicker']) !!}
						<span class="input-group-btn">{!! BsForm::button('<i class="fa fa-check"></i>', ['type' => 'submit', 'class' => 'btn btn-sm btn-success']) !!}</span>
					</div>
					{!! BsForm::close() !!}
				</td>
				<td>{{ $customer->phone }}</td>
				<td>{{ money_format($customer->balance_due)  }}</td>
				<td>{{ $customer->undispatched_orders }}</td>
				<td>{{ $customer->balance_show ? "No" : "Yes" }}</td>
			</tr>
		@endforeach
	</table>
@endif
