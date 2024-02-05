@extends('email')

@section('content')

<p>Hi {{ $user->first_name }},</p>
<p>We have now received your payment of {{ $customPayment->amount_formatted }} which will shortly be allocated to your account.</p>
<p>Thank you for your business.</p>

@endsection

@section('regards')
    Regards,<br/>Recomm
@endsection