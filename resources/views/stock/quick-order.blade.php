@extends('app')

@section('title', "Quick Order")

@section('content')

	<div class="container">
		@include('messages')
		{!! BsForm::open(['route' => 'stock.quick-order', 'method' => 'POST']) !!}
		{!! BsForm::groupTextarea('refs', null, ['placeholder' => 'Separated by new lines or spaces or commas...'], ['label' => '3rd Party Refs / IMEIs']) !!}
		{!! BsForm::submit('Create Order') !!}
		{!! BsForm::submit('Create Batch', ['name' => 'batch']) !!}
		{!! BsForm::close() !!}
	</div>

	@if(isset($ids) && count($ids) && isset($create))
		{!! BsForm::open(['route' => 'sales.new', 'method' => 'post', 'id' => 'quick-order-create-sale-form']) !!}
			@foreach($ids as $id)
				{!! BsForm::hidden('ids[]', $id) !!}
			@endforeach
		{!! BsForm::close() !!}
	@endif

@endsection

@section('scripts')
	<script>
		$('#quick-order-create-sale-form').submit();
	</script>
@endsection