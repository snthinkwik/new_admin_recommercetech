@extends('app')

@section('title', 'Missing Delivery Fees')

@section('content')

<div class="container">
    <div class="flexbox-md">
        <div class="mb-4">
            <a class="btn btn-default" href="{{ session('admin.ebay.delivery-settings') ?: route('admin.ebay.delivery-settings') }}">Back to list</a>
        </div>        
    </div>
    
    <div class="col-md-12">
        <div id="dpd-list-table-wrapper">
            @include('delivery-settings.missing_delivery_fees')
        </div>
        <div id="dpd-pagination-wrapper">{!! $EbayOrders->appends(Request::all())->render() !!}</div>
    </div>
</div>

@endsection

@endsection
