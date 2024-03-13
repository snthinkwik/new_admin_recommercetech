<?php

use Carbon\Carbon;

$statusList = \App\EbayOrders::getAvailableStatusWithKeys();
?>

@extends('app')

@section('title', 'Ready for invoice')

@section('content')

<div class="container">
    <div class="row mb-4">
        <div class="col-lg-6">
            <a class="btn btn-default" href="{{ session('admin.ebay-orders') ?: route('admin.ebay-orders') }}">Back to list</a>
            <a class="btn btn-default" href="{{route('admin.ebay.ready-for-invoice.export')}}"><i class="fa fa-download mr-2"></i> Export</a>
            <a class="btn btn-default" href="{{ session('admin.ebay.ready-invoice.manually-assigned') ?: route('admin.ebay.ready-invoice.manually-assigned') }}">Manually Assigned</a>
        </div>
        <div class="col-lg-6 text-right d-inline-block text-bold">
            <div class="row">
                <div class="col-lg-6">
                    <p class="mb-0">Net to Invoice: <span class="text-success mr-2">{{money_format($SalesPrice-$Fees-$PayPalPrice-$totalDeliveryCharge-$totalEbayDeliveryCharge)}}</span><i class="fa fa-info-circle" data-toggle="tooltip" data-placement="bottom" html=true title="Net to Invoice = Total to Invoice - Total Fees - Total PayPal Fees - Total Delivery Charges (DPD) - Total Ebay Delivery Charges (Royal Mail 1st & Hermes)"></i> </p>
                    <p class="mb-0">Total to Invoice: <span class="text-success mr-2">{{money_format($SalesPrice)}}</span><i class="fa fa-info-circle" data-toggle="tooltip" data-placement="bottom" html=true title="Sum of Individual Item Price from Order Item table Where Order status is Dispatched AND Order Item has Fees AND No Invoice Number AND Owner is Recomm"></i></p>
                    <p class="mb-0">Total Fees: <span class="text-success mr-2">{{money_format($Fees)}}</span><i class="fa fa-info-circle" data-toggle="tooltip" data-placement="bottom" html=true title="Sum of Fees from eBay Fee table Where No Invoice Number AND Owner is Recomm AND Matched is Yes"></i></p>
                </div>
                <div class="col-lg-6">
                    <p class="mb-0">Total PayPal Fees: <span class="text-success mr-2">{{money_format($PayPalPrice)}}</span><i class="fa fa-info-circle" data-toggle="tooltip" data-placement="bottom" html=true title="Sum of PayPal Fees from Main Order table Where Owner is Recomm AND Status is in 'Dispatched','Refunded','Cancelled'"></i></p>
                    <p class="mb-0">Total Delivery Charges: <span class="text-success mr-2">{{money_format($totalDeliveryCharge + $totalEbayDeliveryCharge)}}</span><i class="fa fa-info-circle" data-toggle="tooltip" data-placement="bottom" html=true title="Sum of Cost from DPD Import table Where Owner is Recomm + Sum of Cost from eBay Delivery Charge table Where Owner is Recomm"></i></p>
                    <p class="mb-0">Fee as a %: <span class="text-success mr-2">{{number_format((float) (($Fees+$PayPalPrice)/$SalesPrice)*100, 2, '.', '')."%"}}</span><i class="fa fa-info-circle" data-toggle="tooltip" data-placement="bottom" html=true title="((Total Fees + Total PayPal Fees) / Total to Invoice) * 100"></i></p>
                    <p class="mb-0">Total Amount Refunded: <span class="text-success mr-2">{{money_format($totalRefundAmount)}}</span><i class="fa fa-info-circle" data-toggle="tooltip" data-placement="bottom" html=true title="Sum of refunded amount from ebay refund table"></i></p>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        {!! BsForm::open(['id' => 'ready-for-invoice-search-form', 'class' => 'spinner mb15', 'method' => 'get']) !!}
        <div class='universal-loader' style="display: none" ><div class='universal-spinner'></div></div>
        <div class="col-sm-2">
            <div class="form-group">
                <select name="field" id="ReadyforInvoiceFilter" class="form-control">
                    <option value="">Select Filter</option>
                    <option value="sales_record_number" @if(Request::input('field')=="sales_record_number") selected @endif>Sales Record No.</option>
                    <option value="item_name" @if(Request::input('field')=="item_name") selected @endif>Item Name</option>
                    <option value="item_number" @if(Request::input('field')=="item_number") selected @endif>Item Number</option>
                    <option value="item_sku" @if(Request::input('field')=="custom_label") selected @endif>Custom Label</option>
                </select>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="form-group">
                {!! BsForm::text('filter_value', Request::input('filter_value'), ['id' => 'ready-for-invoice-filter-search-term', 'placeholder' => 'Search Text']) !!}
            </div>
        </div>

        <div class="col-sm-3">
            <div class="form-group">
                <div class="input-group">
                    <span class="input-group-addon">Sale Type</span>
                    <select class="form-control" name="sale_type">
                        <option value="">-- Select --</option>
                        <option>{{\App\EbayOrderItems::SALE_TYPE_BUY_IT_NOW}}</option>
                        <option>{{\App\EbayOrderItems::SALE_TYPE_AUCTION}}</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="col-sm-2">
            <div class="form-group">
                <div class="input-group">
                    <span class="input-group-addon">Invoice?</span>
                    <select class="form-control" name="invoice">
                        <option value="">-- Select --</option>
                        <option value="Yes">Yes</option>
                        <option value="No">No</option>
                    </select>
                </div>
            </div>
        </div>
        {{--<div class="col-sm-2">
            <div class="form-group">
                <div class="input-group">
                    <span class="input-group-addon">Ready?</span>
                    <select class="form-control" name="ready">
                        <option value="">-- Select --</option>
                        <option value="Yes">Yes</option>
                        <option value="No">No</option>
                    </select>
                </div>
            </div>
        </div>--}}

        <div class="col-sm-4">
            <div class="form-group">
                <div class="input-group">
                    <span class="input-group-addon">Order Status</span>
                    {!! BsForm::select('order_status',['' => 'Please Select']+$statusList, '', ['class' => 'form-control']) !!}
                </div>
            </div>
        </div>

        {!! BsForm::hidden('sort', '',['id'=>'sort']) !!}
        {!! BsForm::hidden('sortO', '',['id'=>'sortO']) !!}
        {!! BsForm::close() !!}
    </div>
    <div id="ready-for-invoice-wrapper">
        @include('ebay-order.ready_for_invoice')
    </div>
    <div id="ready-for-invoice-pagination-wrapper">{!! $OrderItem->appends(Request::all())->render() !!}</div>
</div>
@endsection

@section('nav-right')
<div id="basket-wrapper" class="navbar-right pr0">
    @include('basket.navbar')
</div>
@endsection
