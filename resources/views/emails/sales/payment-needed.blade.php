@extends('email')

@section('content')

<p>Hi {{ $customer->first_name }},</p>
<p>We notice that invoice number {{ $sale->invoice_number }} has not been paid, see invoice attached.</p>
<p>Please pay this invoice by {{ $sale->payment_deadline->format('M j, h:iA') }}.</p>
<p>If payment is not received before this time please note that the stock will be released to the general public again for sale.</p>

@endsection