@extends('app')

@section('title', "Stock Stats")

@section('content')

	<div class="container">

		<h2>Stock Stats</h2>

		<div class="row">
			<div class="col-md-6">
				<table class="table table-bordered table-hover">
					<tr>
						<th>Status</th>
						<th>No. Items</th>
					</tr>
					@foreach($stats as $status => $number)
						<tr>
							<td>{{ $status }}</td>
							<td>{{ $number }}</td>
						</tr>
					@endforeach
				</div>
			</div>
		</div>

	</div>

@endsection