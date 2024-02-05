@extends('email')

@section('content')

<p>Hi {{ $user->first_name }},</p>
<p>Thank you for registering with Recomm.</p>
<p>Please click <a href="{{ route('auth.email-confirm', ['userId' => $user->id, 'code' => $user->email_confirmation]) }}">here</a> to confirm your account.</p>

@endsection