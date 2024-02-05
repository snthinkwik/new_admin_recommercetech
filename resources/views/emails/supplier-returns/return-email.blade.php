<?php
use Carbon\Carbon;
?>
@extends('email')

@section('content')

    <p>Hi {{ $supplier->contact_name }},</p>
    
    <p>Please find attached our RMA for Recomm Return batch {{ $supplierReturn->id }}.</p>
    <p>The returns have been sent today as per below.</p>
    
    <p>
        <strong>No. items returned:</strong> {{ $supplierReturn->items()->count() }}<br/>
                                        
        <strong>Courier:</strong> {{ $supplierReturn->courier }}<br/>
                                        
        <strong>Tracking Number:</strong> {{ $supplierReturn->tracking_number }}<br/>
                                        
        <strong>Dispatch Date:</strong> {{ Carbon::now()->format('d/m/Y') }}<br/>
    </p>
    
    <p>Please credit our account once these devices have been received.</p>
    <p>Please note, this is an automated email.</p>

@endsection

@section('regards')
    Regards,<br/><strong>Recomm</strong>
@endsection