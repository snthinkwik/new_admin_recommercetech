<?php
use App\Email;
?>
@extends('email')

@section('content')

<p>{!! $body !!}</p>

@endsection

@section('regards')
	Kind Regards,<br/><strong>{{ $fromName ?: config('mail.from.name') }}</strong>
	<br><br>
	@if(isset($encrypt_id))
	<small>Click to <a href="{{route('emails.unsubscribe',['id'=>$encrypt_id])}}" class="btn btn-danger"> unsubscribe </a> from these emails</small>
	@endif
@endsection

{{--@section('footer-company')
	@if(isset($user) && $user->registered)
	<p style="font-size:11px; margin:0">
	    Please note this is an automated message as you are signed up to our
	    stock system.
	    <a
	        href="{{ route('account.registered-disable-notifications', ['id' => $user->id, 'email' => $user->email]) }}">Click
	        here</a> to update your notification preferences in your account.
	</p>
	@elseif(isset($user) && !$user->registered)
	<p style="font-size:11px; margin:0">
	    No longer wish to receive our marketing emails? <a
	        href="{{ route('account.disable-notifications', ['id' => $user->id, 'token' => $user->registration_token]) }}">Click
	        here</a> to remove
	    yourself from our mailing list.
	</p>
	@endif
@endsection--}}

{{--
@if(isset($user) && $user && $user->invoice_api_id)
@section('customer-id')
	<p style="font-size:11px; margin:0">RCT ref: {{ $user->invoice_api_id }}</p>
@endsection
@endif--}}
