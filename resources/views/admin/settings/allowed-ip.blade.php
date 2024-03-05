@extends('app')

@section('title', 'Allowed IPs')

@section('content')

	<div class="container">

		@include('admin.settings.nav')
		@include('messages')

		<h2>Allowed IPs</h2>

		<div class="row mb10">
			<div class="col-md-12">
				{!! BsForm::open(['method' => 'post', 'route' => 'admin.settings.allowed-ips-add']) !!}
				<div class="input-group">
					<span class="input-group-addon">IP Address</span>
					{!! BsForm::text('ip_address', null, ['placeholder' => 'IP Address', 'required' => 'required']) !!}
					<span class="input-group-btn">{!! BsForm::submit('Add IP Address') !!}</span>
				</div>
				{!! BsForm::close() !!}
			</div>
		</div>

			@if(!count($ips))
				<div class="alert alert-info">Nothing Found</div>
			@else
				<table class="table table-hover table-bordered">
					<thead>
						<tr>
							<th class="col-xs-1">#</th>
							<th class="col-xs-7">IP Address</th>
							<th class="col-xs-2">Last Login</th>
							<th class="col-xs-2">Delete</th>
						</tr>
					</thead>
					<tbody>
						@foreach($ips as $ip)
							<tr>
								<td>{{ $ip->id }}</td>
								<td>{{ $ip->ip_address }}</td>

								<td>{{ $ip->last_login->format("d/m/y H:i:s") }}</td>
								<td>
									{!! BsForm::open(['route' => 'admin.settings.allowed-ips-remove', 'method' => 'post']) !!}
									{!! BsForm::hidden('id', $ip->id) !!}
									{!! BsForm::submit('Delete',
										['type' => 'submit',
										'class' => 'btn btn-xs btn-block btn-danger confirmed',
										'data-toggle' => 'tooltip', 'title' => "Delete IP", 'data-placement'=>'right',
										'data-confirm' => "Are you sure you want to delete this IP?"])
									!!}
									{!! BsForm::close() !!}
								</td>
							</tr>
						@endforeach
					</tbody>
				</table>
			@endif

	</div>

@endsection