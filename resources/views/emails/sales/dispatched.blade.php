@extends('email')

@section('content')
    <p>Hi {{ $customer->first_name }},</p>
    
    <p>We have sent the goods for your order #{{ $sale->invoice_number }}.</p>
    @if(!$sale->item_name)
        <p>Please find attached a list of your purchase with IMEI numbers.</p>
    @endif

@endsection