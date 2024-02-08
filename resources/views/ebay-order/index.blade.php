<?php
use App\Models\EbayOrders;
?>
@extends('app')

@section('title', 'eBay Orders')

@section('content')

<div class="container">
    @include('messages')
    <div class="row  text-bold text-center mt-5">

        <div class="col-sm-6 col-md-3 col-lg-1-5">
            <a  class="text-white" href="{{route('admin.ebay-orders').'?status='.EbayOrders::STATUS_NEW}}">
                <div class="px-3 pt-4 pb-3 bg-primary mb-4">
                    <h3 class="pb-3">{{$statusCount[0]->total_new}}</h3>
                    <p class="mb-0">New</p>
                </div>
            </a>
        </div>
        <div class="col-sm-6 col-md-3 col-lg-1-5">
            <a class="text-white" href="{{route('admin.ebay-orders').'?status='.EbayOrders::STATUS_DISPATCHED}}">
                <div class="px-3 pt-4 pb-3 bg-success-dark mb-4">
                    <h3 class="pb-3">{{$statusCount[0]->total_dispatched}}</h3>
                    <p class="mb-0">Dispatched</p>
                </div>
            </a>
        </div>
        <div class="col-sm-6 col-md-3 col-lg-1-5">
            <a class="text-white" href="{{route('admin.ebay-orders').'?status='.EbayOrders::STATUS_CANCELLED}}">
                <div class="px-3 pt-4 pb-3 bg-warning-dark mb-4">
                    <h3 class="pb-3 text-white">{{$statusCount[0]->total_cancelled}}</h3>
                    <p class="mb-0 text-white">Cancelled</p>
                </div>
            </a>
        </div>
        <div class="col-sm-6 col-md-3 col-lg-1-5">
            <a class="text-white" href="{{route('admin.ebay-orders').'?status='.EbayOrders::STATUS_REFUNDED}}">
                <div class="px-3 pt-4 pb-3 bg-danger-dark mb-4">
                    <h3 class="pb-3 text-white">{{$statusCount[0]->total_refunded}}</h3>
                    <p class="mb-0 text-white">Refunded</p>
                </div>
            </a>
        </div>
        <div class="col-sm-6 col-md-3 col-lg-1-5">
            <a class="text-white" href="{{route('admin.ebay-orders').'?status='.EbayOrders::STATUS_AWAITING_PAYMENT}}">
                <div class="px-3 pt-4 pb-3 bg-astral-dark mb-4">
                    <h3 class="pb-3 text-white">{{$statusCount[0]->total_awaiting_payment}}</h3>
                    <p class="mb-0 text-white">Awaiting Payment</p>
                </div>
            </a>
        </div>
    </div>
    @if(in_array(Auth::user()->admin_type, ['admin', 'manager']))
    <div  class="show-if-has-error mb15 btn-no-focus">
        {{--<a class="btn btn-link px-0" href="{{route('admin.ebay-order.sync')}}">Sync eBay Order</a>--}}
        {{--@if(in_array(Auth::user()->email, ['sam@recomm.co.uk']))--}}
        	{{--<a class="btn btn-link px-0" href="{{route('ebay.sku.index')}}" >eBay SKU's </a> |--}}
        {{--@endif--}}
        {{--<a class="btn btn-link px-0" href="{{route('ebay-fee.index')}}" >Import eBay Fees </a> |--}}
        {{--<a class="btn btn-link px-0" href="{{route('ebay.history-log')}}">Import History </a> |--}}
        {{--<a class="btn btn-link px-0" href="{{route('ebay.stats')}}">eBay Stats</a> |--}}
        {{--<a class="btn btn-link px-0" href="{{route('admin.ebay.ready-invoice.view')}}">Ready for invoice</a> |--}}
        {{--<a class="btn btn-link px-0" href="{{route('admin.ebay.delivery-settings')}}"></i>Delivery Settings</a> |--}}
        {{--<a class="btn btn-link px-0" href="{{route('admin.ebay.refund')}}"></i>Refunds</a>--}}
    </div>
    @endif
    @include('ebay-order.search-form')

</div>
<div id="ebay-order-items-wrapper">
    @include('ebay-order.list')
</div>
<div id="ebay-order-pagination-wrapper">{!! $ebayOrders->appends(Request::all())->render() !!}</div>
@endsection

@section('nav-right')
<div id="basket-wrapper" class="navbar-right pr0">
    @include('basket.navbar')
</div>
@endsection
