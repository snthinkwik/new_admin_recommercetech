@extends('app')

@section('title', "My Settings")

@section('content')
	
	<div class="container">
		@include('account.nav')

		<h3>Settings</h3>
		{!! Form::model($user, ['route' => 'account.settings.save', 'id' => 'account-settings-form']) !!}
			<div class="checkbox">
				<label>
					{!! BsForm::hidden('marketing_emails_subscribe', 0) !!}
					{!! BsForm::checkbox('marketing_emails_subscribe', 1, null, ['data-toggle' => 'toggle']) !!}
					Marketing Emails
				</label>
			</div>
			<div class="response"></div>
		{!! Form::close() !!}
	</div>
	
@endsection