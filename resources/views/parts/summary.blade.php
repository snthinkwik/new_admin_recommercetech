@extends('app')

@section('title', 'Parts')

@section('content')

	<div class="container">

		@include('messages')
		<p><a class="btn btn-default" href="{{ route('parts') }}">Back to parts</a></p>
		<h2>Summary</h2>
		<div class="row">
			<div class="col-md-6">
				<table class="table table-bordered table-hover">
					<tr>
						<th>Total no. Parts in Stock:</th>
						<td>{{ $data->total_no_parts }}</td>
					</tr>
					<tr>
						<th>Total Value of Parts</th>
						<td>{{ $data->total_value_of_parts }}</td>
					</tr>
				</table>
			</div>
		</div>
	</div>
@endsection