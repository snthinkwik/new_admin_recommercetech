<?php
$deadline = \App\Sale::PAYMENT_WAIT_HOURS;
?>
@extends('email')

@section('content')
    <p>Hi {{ $customer->first_name }},</p>
    <p>Thank you for placing your order with {{ config('app.company_name') }}.</p>
    <p>Attached is your invoice for {{ $sale->amount_formatted }} - payment is due immediately.</p>

    <p>
        Payment method is preferred by Bank Transfer:<br/>
        <strong>Account Name:</strong> Recommerce Ltd<br/>
        <strong>Account no:</strong> 49869160<br/>
        <strong>Sort Code:</strong> 30-98-97<br/>
        <strong>Bank:</strong> Lloyds
    </p>
                        
    <p>Once payment has been made please send remittance to {{ config('mail.finance_address') }}</p>
    <p>We appreciate your business.</p>

@endsection

@section('regards')
    Kind Regards,<br/><strong>Recomm</strong>
@endsection