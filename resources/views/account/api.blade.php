<?php
$user = Auth::user();
?>
@extends('app')

@section('title', 'API')

@section('content')

	<div class="container">
		@include('messages')
		@include('account.nav')

		@if ($user->api_key)
			<h4>Your API key:</h4>
			<div class="well">{{ $user->api_key }}</div>
		@endif

		{!! BsForm::open(['route' => 'account.api.generate-key']) !!}
			{!!
				BsForm::groupSubmit(
					$user->api_key ? 'Regenerate key' : 'Generate key',
					$user->api_key
						? [
								'class' => 'confirmed',
								'data-confirm' => "Are you sure you want to regenerate your API key? You'll need to switch the key " .
									"in your code as your old key will become invalid.",
							]
						: null
				)
			!!}
		{!! BsForm::close() !!}

		<hr>

		@include('account.api-documentation')
	</div>

@endsection