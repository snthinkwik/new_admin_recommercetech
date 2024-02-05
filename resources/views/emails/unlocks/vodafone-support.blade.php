@extends('email')

@section('content')
    <p>Hi <strong>{{$unlock->user->first_name }}</strong>,</p>
    <br>
    <p>Your <strong>{{$unlock->network}}</strong> unlock order has been placed for IMEI: {{ $unlock->imei }}</p>
    <p>We will update you as soon as it's complete.</p>
    <p>If you need any assistance please email <a href="mailto:support@recomm.co.uk">support@recomm.co.uk</a></p>
    <p> Regards,</p>
    <p>Recomm</p>
@endsection