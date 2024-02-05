@extends('app')

@section('title', 'Check what to buy')

@section('content')
	<div class="container">
		<h1>Check what to buy</h1>
		@include('messages')
		{!! BsForm::open(['route' => 'stock.other-recycles-check']) !!}
		{!!
			BsForm::groupTextarea(
				'imeis_list',
				old('imeis_list'),
				['placeholder' => 'One or more IMEI or Serial numbers, separated by new lines, spaces or commas...'],
				['label' => 'Enter IMEI/SERIAL', 'errors_name' => 'imeis', 'errors_all' => true]
			)
		!!}
		{!! BsForm::groupSubmit('Check') !!}
		{!! BsForm::close() !!}

	</div>
@endsection