<?php
use App\Models\Stock;
$lostReasons = ['' => 'Please Select'] + Stock::getAvailableLostReasonsWithKeys();
?>
@extends('app')

@section('title', 'Mark as Lost')

@section('content')

	<div class="container">
		<h2>Mark Items As Lost <small><span class="fa fa-question-circle" data-toggle="tooltip" title="'In Stock' items"></span></small></h2>

		<a href="{{ route('stock-take.view-lost-items') }}" class="btn btn-default">View Lost Items</a>

		@include('messages')

		{!! BsForm::open(['route' => 'stock-take.mark-as-lost-submit', 'id' => 'stock-take-mark-as-lost-form']) !!}
		{!!
			BsForm::groupTextarea(
				'mark_as_lost_list',
				old('mark_as_lost_list'),
				['placeholder' => 'One or more of the following [IMEI numbers, 3rd party ref, RCT Refs], separated by new lines, spaces or commas...'],
				['label' => 'Enter IMEI/3rd-party-ref/RCT-ref', 'errors_name' => 'imeis', 'errors_all' => true]
			)
		!!}
		{!! BsForm::groupSelect('lost_reason', $lostReasons, null, ['required' => 'required']) !!}
		{!! BsForm::groupSubmit('Mark As Lost', ['class' => 'btn-block']) !!}
		{!! BsForm::close() !!}
	</div>

@endsection
