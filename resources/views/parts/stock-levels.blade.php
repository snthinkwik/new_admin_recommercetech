@extends('app')

@section('title', 'Parts')

@section('content')

	<div class="container">

		@include('messages')
		<p><a class="btn btn-default" href="{{ route('parts') }}">Back to parts</a></p>
		<h2>Stock Levels</h2>
		<div class="row">
			@if(count($parts) == 0)
				<div class="alert alert-danger">No Parts</div>
			@else
				<div class="col-md-12">
					{!! BsForm::open(['route' => 'parts.stock-levels-update']) !!}
					@foreach($parts as $part)
						<div class="row mb10">
							{!! Form::hidden('part['.$part->id.'][id]', $part->id) !!}
							<div class="col-md-1">
								{!! Form::label('id', 'Part No.') !!}
								{!! Form::text('id', $part->id, ['disabled', 'class' => 'form-control']) !!}
							</div>
							<div class="col-md-5">
								{!! Form::label('name', 'Part Name (Name - Colour - Type)') !!}
								{!! Form::text('long_name', $part->long_name, ['disabled', 'class' => 'form-control']) !!}
							</div>
							<div class="col-md-2">
								{!! Form::label('cost', 'Part Cost') !!}
								<div class="input-group">
									<div class="input-group-addon">£</div>
									{!! Form::number('cost', $part->cost, ['disabled', 'class' => 'form-control']) !!}
								</div>
							</div>
							<div class="col-md-2">
								{!! Form::label('quantity_inbound', 'Inbound Qty') !!}
								{!! Form::number('part['.$part->id.'][quantity_inbound]', $part->quantity_inbound, ['class' => 'form-control']) !!}
							</div>
							<div class="col-md-2">
								{!! Form::label('quantity', 'RCT Qty') !!}
								{!! Form::number('part['.$part->id.'][quantity]', $part->quantity, ['class' => 'form-control']) !!}
							</div>
						</div>
					@endforeach
					{!! BsForm::submit('Update', ['class' => 'mt10 btn btn-primary btn-block']) !!}
					{!! BsForm::close() !!}
				</div>
			@endif
		</div>
	</div>
@endsection