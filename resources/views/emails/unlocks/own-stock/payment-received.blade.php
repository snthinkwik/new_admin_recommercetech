@extends('email')

@section('content')


    <p>Hi Chelsea</p>
    <p>A payment has been received for the following unlock order:</p>
    <p>
        <strong>Unlock Order ID:</strong> {{ $order->id }}<br/>
                                        
        <strong>Customer Name:</strong> {{ $customer }}<br/>
                                        
        @if($order->invoice_total_amount > 0)
            <strong>Invoice Total Amount:</strong> &pound;{{ $order->invoice_total_amount }}<br/>
        @else
            <strong>Invoice Total Amount:</strong> {{ $order->amount_formatted }}<br/>
        @endif
        <strong>QuickBooks Invoice Number:</strong> {{ $order->invoice_api_id }}<br/>
    </p>
@endsection

@section('regards')
    Regards,<br/><strong>Recomm Bot</strong>
@endsection