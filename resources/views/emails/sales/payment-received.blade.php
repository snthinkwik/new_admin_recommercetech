@extends('email')

@section('content')
<p>Hi Chris</p>
<p>A payment has been received for the following order:</p>
<p>
    <strong>Order ID:</strong> {{ $sale->id }}<br/>
                                        
    <strong>Order Value:</strong> {{ $sale->amount_formatted }}<br/>
                                        
    <strong>Customer Name:</strong> {{ $customer }}<br/>
</p>

@endsection

@section('regards')
    Regards,<br/><strong>Recomm Bot</strong>
@endsection