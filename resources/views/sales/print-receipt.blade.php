@extends('app')

@section('title', 'Print Receipt')


@section('content')

	<div class="container">

		<h2>Print Receipt</h2>

		@include('messages')

		<div class="row">
			<div class="col-md-4">
				{!! BsForm::open(['method' => 'get', 'route' => 'sales.print-receipt']) !!}
				<div class="form-group">
					<div class="input-group">
					<span class="input-group-addon">
						Item RCT Ref
					</span>
						{!! BsForm::text('ref', null, ['placeholder' => 'RCT Ref', 'required' => 'required']) !!}
						<span class="input-group-btn">
						{!! BsForm::button('<i class="fa fa-search"></i>', ['type' => 'submit']) !!}
					</span>
					</div>
				</div>
				{!! BsForm::close() !!}
			</div>
		</div>

	</div>

@endsection