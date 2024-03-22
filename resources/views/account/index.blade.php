@extends('app')

@section('title', "My account")

@section('content')

	<div class="container">
		@include('messages')
		@include('account.nav')

		<h2>Account</h2>
		<div class="row">
			<div class="col-md-4">
				@include('account.form')
			</div>
			<div class="col-md-4">
				@if($unpaid)
					<div class="well">
					You have {{ $unpaid }} unpaid orders, click here to pay now.
					<a class="btn btn-default btn-block" href="{{ route('sales') }}">Pay Now</a>
					</div>
				@endif
			</div>
			<div class="col-md-4">
				<div class="panel panel-default">
					<div class="panel-heading">Set new password</div>
					<div class="panel-body">
						{!! BsForm::open(['method' => 'post', 'route' => 'account.change-password']) !!}
							{!! Form::label('current_password', 'Current Password') !!}
							{!! Form::Password('current_password', ['placeholder' => 'Current Password', 'class' => 'form-control', 'required' => 'required']) !!}
							@error('current_password') @enderror
							{!! Form::label('password', 'New Password') !!}
							{!! Form::Password('password', ['placeholder' => 'New Password', 'class' => 'form-control', 'required' => 'required']) !!}
							@error('password') @enderror
							{!! Form::label('password_confirmation', 'Confirm Password') !!}
							{!! Form::Password('password_confirmation', ['placeholder' => 'Confirm Password', 'class' => 'form-control', 'required' => 'required']) !!}
							@error('password_confirmation') @enderror
							{!! BsForm::submit('Change Password', ['class' => 'btn-block btn-default btn-sm']) !!}
						{!! BsForm::close() !!}
					</div>
				</div>
			</div>
		</div>
	</div>

@endsection
