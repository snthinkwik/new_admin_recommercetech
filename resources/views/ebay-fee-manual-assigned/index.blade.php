<?php
$fee_type = \App\EbayFees::select('fee_type')->groupBy('fee_type')->get();
?>
@extends('app')

@section('title', 'Manually Assigned')

@section('content')
<div class="container">
    <div class="flexbox-md">
        <div class="mb-4">
            <a class="btn btn-default" href="{{ session('admin.ebay.ready-invoice.view') ?: route('admin.ebay.ready-invoice.view') }}">Back to list</a>
            <a class="btn btn-default" href="{{route('admin.ebay.ready-invoice.manually-assigned.export')}}"><i class="fa fa-download "></i> Export</a>
        </div>
        <div class="text-right d-inline-block text-bold mb-4">
            Manual Fees: <span class="text-success mr-2">{{money_format(config('app.money_format'),  $totalManualFees)}}</span>
        </div>
    </div>
    <div class="row">

        {!! BsForm::open(['id' => 'manual-assigned-search-form', 'class' => 'spinner mb15', 'method' => 'get']) !!}
        <div class='universal-loader' style="display: none" ><div class='universal-spinner'></div></div>
        <div class="col-sm-3">
            <div class="form-group">
                <select name="field"  class="form-control">
                    <option value="">Select Filter</option>
                    <option value="item_number" @if(Request::input('field')=="item_number") selected @endif>Item Number</option>
                    <option value="fee_title" @if(Request::input('field')=="title") selected @endif >Title</option>
                </select>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="form-group">
                {!! BsForm::text('filter_value', Request::input('filter_value'), ['id' => 'manual-assigned-search-item', 'placeholder' => 'Search', 'size' => 20]) !!}
            </div>
        </div>
        <div class="col-sm-3">
            <div class="form-group">
                <div class="input-group">
                    <span class="input-group-addon">Fee Type</span>
                    <select name="fee_type" class="form-control">
                        <option value="">Please Select Fee Type</option>
                        @if(count($fee_type)>0)
                        @foreach($fee_type as $type)
                        <option value="{{$type->fee_type}}">{{$type->fee_type}}</option>
                        @endforeach
                        @endif

                    </select>
                </div>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="form-group">
                <div class="input-group">
                    <span class="input-group-addon">Invoiced?</span>
                    <select name="invoice" class="form-control">
                        <option value="">Please Select </option>
                        <option value="Yes">Yes</option>
                        <option value="No">No</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="form-group">
                <div class="input-group">
                    <span class="input-group-addon">Date</span>
                    <?php
                    $date = null;
                    if (Request::input('date')) {
                        $date = date_format(date_create(Request::input('date')), "Y-m-d");
                    }
                    ?>
                    <input type="date" value="{{$date}}" name="date" class="form-control">
                </div>
            </div>
        </div>
        {!! BsForm::hidden('sort', '',['id'=>'sort']) !!}
        {!! BsForm::hidden('sortO', '',['id'=>'sortO']) !!}
        {!! BsForm::close() !!}
    </div>
    <div id="manual-assigned-items-wrapper">
        @include('ebay-fee-manual-assigned.list')
    </div>
    <div id="manual-assigned-pagination-wrapper">{!! $ManuallyAssignFee->appends(Request::all())->render() !!}</div>
</div>
@endsection

@section('nav-right')
<div id="basket-wrapper" class="navbar-right pr0">
    @include('basket.navbar')
</div>
@endsection