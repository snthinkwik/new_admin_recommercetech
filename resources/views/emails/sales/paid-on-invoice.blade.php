@extends('email')

@section('content')

<p>Hi {{ $customer->first_name }},</p>
<p>We have now added terms to your invoice and it needs to be settled within 14 days from today.</p>
<p>Your order will now be picked and dispatched.</p>
@endsection

@section('regards')
    Regards,<br/><strong>Recomm</strong>
@endsection