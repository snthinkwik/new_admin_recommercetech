@extends('app')

@section('title', "Modify order")

@section('content')

	<div class="container">
		@include('messages')
		<div class="row">
			<div class="col-md-4">

				<h2>Replace in Sale</h2>
				{!! BsForm::open(['route' => 'sales.swap-item']) !!}
					{!!
						BsForm::groupText(
							'original_ref',
							old('original_ref'),
							['placeholder' => 'IMEI, serial number or 3rd-party ref'],
							['label' => 'Original']
						)
					!!}
					{!!
						BsForm::groupText(
							'replace_ref',
							old('replace_ref'),
							['placeholder' => 'IMEI, serial number or 3rd-party ref'],
							['label' => 'Replacement']
						)
					!!}
					{!! BsForm::groupSubmit('Modify order') !!}
				{!! BsForm::close() !!}

				<hr>

				<h2>Remove from Sale</h2>
				{!! BsForm::open(['route' => 'sales.remove-item']) !!}
					{!! BsForm::groupText('ref', old('ref'), ['placeholder' => 'IMEI, serial or 3rd-party ref']) !!}
					{!! BsForm::groupSubmit('Modify order') !!}
				{!! BsForm::close() !!}

			</div>
		</div>
	</div>

@endsection