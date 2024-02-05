@extends('email')

@section('content')

<p>Hi {{ $order->user->first_name }},</p>

<p>Thank you for your unlock order.</p>
<p>Your unlock request has now been received and the IMEI's are being processed for unlock from the {{ $order->network }} network.</p>

<p>Here’s a list of IMEI’s you requested to be unlocked:</p>

@foreach ($order->imeis as $imei)
    {{ $imei }}<br/>
@endforeach
                            
<p>We will send you another email as soon as your device has been unlocked.</p>
                            

@endsection

@section('regards')
    Regards,<br/>Recomm Support
@endsection