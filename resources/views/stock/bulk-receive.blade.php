@extends('app')

@section('title', 'Bulk Receive')

@section('content')

	<div class="container">
		<h2>Bulk Receive</h2>

		@include('messages')

		{!! BsForm::open(['route' => 'stock.bulk-receive', 'id' => 'stock-bulk-receive-form']) !!}
		{!!
			BsForm::groupTextarea(
				'stock_bulk_receive_list',
				old('stock_bulk_receive_list'),
				['placeholder' => 'One or more of the following [IMEI numbers, 3rd party ref, RCT Refs], separated by new lines, spaces or commas...'],
				['label' => 'Enter IMEI/3rd-party-ref/RCT-ref', 'errors_name' => 'imeis', 'errors_all' => true]
			)
		!!}
		{!! BsForm::groupSubmit('Bulk Receive', ['class' => 'btn-block']) !!}
		{!! BsForm::close() !!}
	</div>

@endsection