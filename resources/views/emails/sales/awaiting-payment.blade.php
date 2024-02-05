@extends('email')

@section('content')

    <p>Hi {{ $customer->first_name }},</p>

    <p>Thank you for your recent order from Recomm.</p>
    
    <p>
        <strong>Order Date:</strong> {{ $sale->created_at->format('jS F Y') }}<br/>
                                        
        <strong>Order Value:</strong> {{ $sale->amount_formatted }}<br/>
    </p>
    
    <p>
        Payment method is preferred by Bank Transfer:<br/>
        <strong>Account Name:</strong> Recommerce Ltd<br/>
        <strong>Account no:</strong> 49869160<br/>
        <strong>Sort Code:</strong> 30-98-97<br/>
        <strong>Bank:</strong> Lloyds
    </p>
    
    <p>We have not received payment as of yet for this order.</p>
    <p>Please can you advise when you can make payment?</p>
    {{--<p>Click <a href="{{ route('sales') }}" target="_blank">here</a> to make a payment online now.</p>--}}
    <p>Please note that goods will not be shipped until payment has been received and if payment is not received after consecutive days your order may be cancelled.</p>
    <p>Please note this is an automated email. If you have already paid please disregard this message.</p>

@endsection

@section('regards')
    Kind Regards,<br/><strong>Recomm</strong>
@endsection