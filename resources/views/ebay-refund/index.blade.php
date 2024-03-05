@extends('app')

@section('title', 'eBay Refund')

@section('content')

<div class="container">
    @include('messages')
    @include('ebay-refund.search-form')
    <div id="refund-ebay-order-items-wrapper">
        @include('ebay-refund.list')
    </div>
    <div id="refund-ebay-order-pagination-wrapper">{!! $eBayRefund->appends(Request::all())->render() !!}</div>
</div>
@endsection

@section('nav-right')
<div id="basket-wrapper" class="navbar-right pr0">
    @include('basket.navbar')
</div>
@endsection