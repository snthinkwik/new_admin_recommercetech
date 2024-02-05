<?php
use App\OtherRecycle;
$recyclers = OtherRecycle::getOtherRecyclers();
?>
@extends('app')

@section('title', 'Other Recycles')

@section('content')
	<div class="container">
		<h1>Other Recyclers Recycled</h1>
		@include('messages')
		{!! BsForm::open(['route' => 'stock.other-recycles']) !!}
		{!!
			BsForm::groupTextarea(
				'imeis_list',
				old('imeis_list'),
				['placeholder' => 'One or more IMEI numbers, separated by new lines, spaces or commas...'],
				['label' => 'Enter IMEI', 'errors_name' => 'imeis', 'errors_all' => true]
			)
		!!}
		{!! BsForm::groupSelect('recycler', array_combine($recyclers, $recyclers)) !!}
		{!! BsForm::groupSubmit('Submit') !!}
		{!! BsForm::close() !!}

	</div>
@endsection