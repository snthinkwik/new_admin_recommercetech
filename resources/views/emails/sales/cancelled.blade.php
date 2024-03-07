@extends('email')

@section('content')
	<p>Hi {{ $customer['first_name'] }},</p>
	<p>Your order for invoice #{{ $sale->invoice_number }} has now been cancelled.</p>
	<p>Please let us know if you require any further assistance.</p>
@endsection
