<?php
use App\User;
?>
@extends('email')

@section('content')
    <p>Hi {{ $customer->first_name }},</p>
    <p>Thank you for placing your order with Recomm.</p>
    <p>Your tracking number is {{ $sale->tracking_number }}</p>
    <p>Please note that this tracking number will not come live until the courier has collected the devices at 7pm this evening.</p>
    @if($sale->courier && $sale->courier_website)
        <p>You can track your parcel on {{ $sale->courier }}'s website: <a href="{{ $sale->courier_website }}">{{ $sale->courier_website }}</a></p>
    @elseif(!$sale->courier)
        @if ($user->location === User::LOCATION_UK)
            <p>You can track your parcel on UK Mail's website: https://www.ukmail.com/manage-my-delivery/manage-my-delivery</p>
        @else
            <p>You can track your parcel on TNT's website: https://www.tnt.com/express/en_gb/site/home/applications/tracking.html</p>
        @endif
    @endif
    <p>Please do let us know if you have any questions.</p>

@endsection