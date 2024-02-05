@extends('email')

@section('content')

    <p>Hi {{ $user->first_name }},</p>
    <p>Unfortunately the original carrier failed to collect the
        goods from our offices at Recomm. To prevent further delay we have re-sent your
        order via the following tracking details:
    </p>
    <p>
        <strong>Courier:</strong> {{ $sale->courier }}<br/>
        <strong>Tracking number:</strong> {{ $sale->tracking_number }}<br/>
    </p>

    <p>I apologise for any inconvenience caused.</p>
@endsection

@section('regards')
    Regards,<br/><strong>Recomm Support</strong>
@endsection