@extends('email')

@section('content')

<p>Hi {{ $supplierReturn->supplier->contact_name }},</p>

<p>
    We have returned the below to you but are yet to receive a
    credit for them. Please can you process this today and send me a copy of the credit note?
</p>


<p>
    <strong>Recomm Reference:</strong> {{ $supplierReturn->id }}<br/>
                                        
    <strong>No. Phones Returned:</strong> {{ $supplierReturn->items()->count() }}<br/>
                                        
    <strong>Credit Value:</strong> {{ $supplierReturn->total_purchase_value_formatted }}<br/>
                                        
    <strong>Date Returned:</strong> {{ $supplierReturn->updated_at->format('d/m/Y') }}<br/>
                                        
    <strong>Tracking Number:</strong> {{ $supplierReturn->tracking_number }}<br/>
                                        
    <strong>Courier:</strong> {{ $supplierReturn->courier }}<br/>
</p>

<p>I have attached the RMA again for your records.</p>

<p>I would appreciate if you could look into this at your earliest convenience.</p>
<p>
    Please note this is an automated email. If you have already provided a
    credit note for these returns please can you reply with the credit note so I can update our records?
</p>
@endsection

@section('regards')
    Kind Regards,<br/><strong>Recomm</strong>
@endsection