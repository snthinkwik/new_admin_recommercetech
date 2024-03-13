@extends('app')

@section('title', "Unlock your " . config('app.name') . " stock")

@section('content')

	<div class="container">
		<h1>Unlock your Recomm stock</h1>
		@if ($imeiMessages)
			@foreach ($imeiMessages as $imeiMessage)
				<p class="p5 bg-{{ $imeiMessage['htmlClass'] }}">{{ $imeiMessage['text'] }}</p>
			@endforeach
		@endif
		<p>Is your device still locked to a network?</p>
		<p>We can now resolve this automatically by you submitting the IMEI number:</p>
		{!! BsForm::open(['route' => 'unlocks.add-as-user']) !!}
			{!!
				BsForm::groupTextarea(
					'imeis_list',
					old('imeis_list'),
					['placeholder' => 'One or more IMEI numbers, separated by new lines, spaces or commas...'],
					['label' => 'Enter IMEI', 'errors_name' => 'imeis', 'errors_all' => true]
				)
			!!}
			{!! BsForm::groupSubmit('Go') !!}
		{!! BsForm::close() !!}
	</div>

@endsection