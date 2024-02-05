<?php
use Illuminate\Support\Facades\Request;
?>
@extends('app')

@section('title', 'Check SKU')

@section('content')

	<div class="container">
		<div class="row">
			<div class="col-md-4">
				{!! Form::open(['route' => 'stock.sku', 'method' => 'get']) !!}
					<div class="form-group">
						<label for="sku">SKU</label>
						{!! Form::text('sku', Request::input('sku'), ['class' => 'form-control', 'id' => 'sku']) !!}
					</div>
					<div class="form-group">
						{!! Form::submit('Check', ['class' => 'btn btn-primary']) !!}
						<a class="btn btn-default" href="{{ session('stock.last_url') ?: route('stock') }}">Back to list</a>
					</div>
				{!! Form::close() !!}
			</div>
		</div>

		@if ($sku)
			<table class="table table-striped">
				<thead>
					<tr>
						<th></th>
						<th>Short</th>
						<th>Long</th>
					</tr>
				</thead>
				<tbody>
					@foreach ($sku as $part => $data)
						<tr>
							<td>{{ ucfirst($part) }}</td>
							<td>
								{{ $data['short'] }}
							</td>
							<td>
								{{ $data['long'] ?: '<unrecognised>' }}
							</td>
						</tr>
					@endforeach
				</tbody>
			</table>
		@endif
	</div>

@endsection