@extends('app')

@section('title', "Disable notifications")

@section('content')

	<div class="container">

		@include('messages')

		@if ($user && !$user->marketing_emails_subscribe && !session('account.just_unsubscribed'))
			<div class="alert alert-info">
				You are already removed from our mailing list. If you're still receiving emails, please contact us.
			</div>
		@elseif ($user && $user->marketing_emails_subscribe)
			<h1>Disable notifications</h1>
			<p>We'll disable email notifications for {{ $user->email }}.</p>
			{!! BsForm::open(['route' => 'account.registered-disable-notifications.save']) !!}
			{!! BsForm::groupSubmit('Confirm', ['btn-success']) !!}
			{!! BsForm::hidden('id', Request::input('id')) !!}
			{!! BsForm::hidden('email', Request::input('email')) !!}
			{!! BsForm::close() !!}
		@elseif (!$user)
			<div class="row">
				<div class="col-md-6 col-md-offset-3">
					<div class="alert alert-danger">
						User not found. Please make sure the link you used to reach this page is complete. Perhaps you copied it from
						the email and missed the last character? If you're sure the link is correct, please contact us.
					</div>
				</div>
			</div>
		@endif

	</div>

@endsection