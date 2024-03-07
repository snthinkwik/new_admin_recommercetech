@extends('app')

@section('title', 'Create Custom Batch')

@section('content')
	<div class="container">
		@include('messages')
		<div class="row">
			<div class="col-md-12">
				<h2>New Custom Batch</h2>

				{!! BsForm::open(['method' => 'post', 'route' => 'batches.new-custom-submit', 'files' => 'true']) !!}
				{!! BsForm::groupText('name', null, ['required' => 'required']) !!}
				{!! BsForm::groupTextarea('description') !!}

				{!! Form::label('image', 'Image Upload') !!}
				{!! BsForm::file('image') !!}

				{!! Form::label('file', 'File Upload') !!}
				{!! BsForm::file('file') !!}

				<div class="form-group">
					{!! Form::label('asking_price', 'Asking Price') !!}
					<div class="input-group">
						<span class="input-group-addon">&pound;</span>
						{!! BsForm::number('asking_price', null, ['step' => 0.01]) !!}
					</div>
				</div>

				{!! BsForm::groupSubmit('Create', ['class' => 'btn-block']) !!}
				{!! BsForm::close() !!}
			</div>
		</div>
	</div>
@endsection