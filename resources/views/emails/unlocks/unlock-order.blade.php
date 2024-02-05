<?php
$deadline = \App\Sale::PAYMENT_WAIT_HOURS;
?>
@extends('email')

@section('content')

<p>Hi {{ $customer->first_name }},</p>

<p>Thank you for placing your unlock order with {{ config('app.company_name') }}.</p>
<p>Attached is your invoice for {{ $order->amount_formatted }} - payment is due immediately.</p>
<p>Please note if payment is not received within {{ $deadline > 24 && $deadline % 24 === 0 ? $deadline / 24 . ' days' : $deadline . ' hours' }} your unlock order will be cancelled.</p>
<p>
    Payment method is preferred by Bank Transfer:<br>
    Account Name: Recommerce Ltd<br/>
    Account no: 49869160<br/>
    Sort Code: 30-98-97<br/>
    Bank: Lloyds
    </p>
<p>For orders under £300 we accept payment over the phone: 01494 303600 (Mon-Fri 8am to 6pm) when calling please quote your invoice number.</p>
<p>Once payment has been made please send remittance to {{ config('mail.finance_address') }}</p>
<p> We appreciate your business.</p>
@endsection