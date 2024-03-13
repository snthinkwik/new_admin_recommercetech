@if(!count($suppliers))
	<div class="alert alert-info">Nothing Found</div>
@else
	<table class="table table-bordered table-hover">
		<tr>
			<th>ID</th>
			<th>Name</th>
			<th>Address</th>
			<th>Email</th>
			<th>Contact Name</th>
			<th>Returns Email</th>
			<th class="text-center" title="edit"><i class="fa fa-pencil"></i></th>
			<th class="text-center" title="delete"><i class="fa fa-trash"></i></th>
		</tr>
		@foreach($suppliers as $supplier)
			<tr>
				<td>{{ $supplier->id }}</td>
				<td>{{ $supplier->name }}</td>
				<td>{{ $supplier->address_long }}</td>
				<td>{{ $supplier->email_address }}</td>
				<td>{{ $supplier->contact_name }}</td>
				<td>{{ $supplier->returns_email_address }}</td>
				<td><a class="btn btn-sm btn-success" href="{{ route('suppliers.single', ['id' => $supplier->id]) }}"><i class="fa fa-pencil"></i></a></td>
				<td><a class="btn btn-sm btn-danger" href="{{route('suppliers.delete',['id'=>$supplier->id])}}" onclick="return confirm('Are you sure delete this supplier?')"> <i class="fa fa-trash" title="delete"></i></a> </td>
			</tr>
		@endforeach
	</table>
@endif