@extends('email')

@section('content')

<p>Hi {{ $customer->first_name }},</p>
<p>Thank you for placing your order with Recomm.</p>

<p>We have now received payment for invoice number {{ $sale->invoice_number }}.</p>

<p>We will now prepare your goods for dispatch and notify you once they have been sent.</p>

@if($sale->stock()->count())
    <p>Please find attached a list of your purchase with IMEI numbers.</p>
@endif

@endsection