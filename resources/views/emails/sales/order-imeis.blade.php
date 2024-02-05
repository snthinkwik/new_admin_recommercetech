@extends('email')

@section('content')
    <p>Hi {{ $customer->first_name }},</p>
    <p>Please find attached the IMEI's that you have requested for order #{{ $sale->invoice_number }}</p>
@endsection

@section('regards')
    Regards,<br/><strong>Recomm Sales</strong>
@endsection