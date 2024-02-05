@extends('app')

@section('title', 'Location')

@section('content')
	
	<div class="container">
		{!! Form::open(['route' => 'stock.locations.save', 'id' => 'location-config', 'class' => 'form-inline mb15']) !!}
			<div class="form-group">
				{!! Form::text('ref', null, ['class' => 'form-control', 'placeholder' => '3rd-party ref']) !!}
			</div>
			<div class="form-group">
				{!! Form::text('location', null, ['class' => 'form-control', 'placeholder' => 'Location']) !!}
			</div>
			<div class="form-group">
				{!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
			</div>
		{!! Form::close() !!}
		
		<div id="location-response"></div>
		<div id="location-recent-actions" class="small">
			<h5 class="hide">Recent actions:</h5>
		</div>
	</div>
	
@endsection