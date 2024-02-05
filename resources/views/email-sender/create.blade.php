@extends('app')

@section('title', "Send an email")

@section('content')

	<div class="container">
		<p>{!! link_to_route('emails', 'Back to list', [], ['class' => 'btn btn-default']) !!}</p>
		<div class="row">
			<div class="col-md-6">
				@include('email-sender.form')
			</div>
			<div class="col-md-6">
				<p>Email preview:</p>
				<iframe id="email-preview"></iframe>
			</div>
		</div>
	</div>

@endsection