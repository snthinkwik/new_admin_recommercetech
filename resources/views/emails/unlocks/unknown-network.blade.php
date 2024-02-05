@extends('email')

@section('content')

    <p>Hi @if($unlock->user_id){{ $unlock->user->first_name }} @endif,</p>

    <p>We are now processing {{ $unlock->imei }} for unlock.</p>
    <p>As we do not know the network we have requested the network from Apple servers.</p>
    <p>As soon as we’ve found the network we will drop you an email and let you know we are processing your unlock.</p>

@endsection

@section('regards')
    Regards,<br/>Recomm Support
@endsection
