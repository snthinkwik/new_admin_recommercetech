<?php

use Illuminate\Support\Facades\Request;

$ownerList = \App\EbayOrderItems::getAvailableOwnerWithKeys();
$deliverySettingsList = \App\DeliverySettings::all();
?>
@extends('app')

@section('title', 'eBay Skus')



@section('nav-right')
@if (Auth::user()->type !== 'user' && in_array(Auth::user()->admin_type, ['admin', 'manager']))
<div class="navbar-form navbar-right pr0">
    <div class="btn-group">
        <a href="{{ route('ebay.export.unassigned') }}" class="btn btn-default">
            Export Unassigned
        </a>
    </div>
</div>
@endif
@endsection
@section('content')

<div class="container">
    <div class="row">
        <div class="col-sm-12 col-lg-5">
            <div class="mb-4">
                <a class="btn btn-default" href="{{ session('admin.ebay-orders') ?: route('admin.ebay-orders') }}">Back
                    to list</a>
                <a class="btn btn-default" href="{{route('ebay.sku.export')}}">Export</a>
                <a href="#ebay-fees" data-toggle="collapse" class="btn btn-default">Import</a>
                <a class="btn btn-default" href="{{route('ebay-sku.cron')}}">Assign Owner</a>
                <a class="btn btn-default mt-2" href="{{route('ebay.sku.unassigned')}}">Unassigned</a>
            </div>
        </div>
        <div class="col-sm-12 col-lg-7">
            <div class="mb-4 text-bold text-right">
                <div class="row">
                    <div class="col-sm-12 col-lg-4">
                        Total number of SKU's: <span class="text-success mr-2">{{$totalNumberSKU}}</span><br/>
                        TRG: <span class="text-danger mr-2">{{$ownerCount[0]->total_trg}}</span><br/>
                        Recomm: <span class="text-warning mr-2">{{$ownerCount[0]->total_recomm}}</span>
                    </div>
                    <div class="col-sm-12 col-lg-4">
                        CMT: <span class="text-success mr-2">{{$ownerCount[0]->total_cmt}}</span><br/>
                        CMN: <span class="text-success mr-2">{{$ownerCount[0]->total_cmn}}</span><br/>
                        Refurbstore: <span class="text-danger mr-2">{{$ownerCount[0]->total_refurbstore}}</span>
                    </div>
                    <div class="col-sm-12 col-lg-4">
                        Unknown: <span class="text-danger mr-2">{{$ownerCount[0]->total_unknown}}</span><br/>
                        LCD Buyback: <span class="text-danger mr-2">{{$ownerCount[0]->total_lcd_buyback}}</span><br/>
                        Unassigned: <span class="text-success mr-2">{{$ownerCount[0]->total_unassigned}}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div id="ebay-fees" class="collapse {{ session('ebay.csv_errors') ? 'in' : '' }} show-if-has-error mb15">
        <p class="mv20"><a href="{{ route('ebay-sku.template') }}">Click here to download a CSV template.</a></p>
        <p><a href="#ebay-fees" data-toggle="collapse"><i class="fa fa-close"></i></a></p>
        @include('ebay-sku.import-csv')
    </div>

    @include('ebay-order.search-form-sku')


    <div class="alert alert-success" role="alert" id="message" style="display: none">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span
                aria-hidden="true">&times;</span></button>
        Update Shipping Method successfully
    </div>
    @include('messages')
    <div class="row">
        <div class="col-md-4">
            <div class="panel panel-default  ">
                <div class="panel-heading">Add Skus</div>
                <div class="panel-body">
                    {!! Form::open(['route' => 'ebay.sku.save', 'method' => 'post']) !!}
                    <div class="row">

                        <div class="col-sm-12">
                            <div class="form-group">
                                <label for="sku">SKU</label>
                                {!! Form::text('sku', Request::input('sku'), ['class' => 'form-control', 'id' => 'sku','required']) !!}
                            </div>
                        </div>
                        <div class="col-sm-12">
                            <div class="form-group">
                                <label for="sku">Owner</label>
                                <select class="form-control owner-select2" name="owner" required>
                                    @foreach($ownerList as $owner)
                                    <option value="{{$owner}}">{{$owner}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-12">
                            <div class="form-group">
                                <label for="location">Location</label>
                                {!! Form::text('location', Request::input('location'), ['class' => 'form-control', 'id' => 'location']) !!}
                            </div>
                        </div>
                        <div class="col-sm-12">
                            <div class="form-group">
                                <label for="shipping_method">Shipping Method</label>
                                <select name="shippingMethod" class="form-control shipping-select2">
                                    <option value="">Select Shipping Method</option>
                                    @foreach($deliverySettingsList as $deliverySettings)
                                    <option value="{{$deliverySettings->id}}">{{$deliverySettings->carrier.' - '.$deliverySettings->service_name}}</option>
                                    @endforeach
                                </select>
                            </div>

                        </div>
                    </div>
                    <div class="form-group">
                        {!! Form::submit('Add', ['class' => 'btn btn-primary btn-block']) !!}
                    </div>
                    {!! Form::close() !!}
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div id="universal-table-wrapper">
                @include('ebay-sku.list')
            </div>
            <div id="universal-pagination-wrapper">{!! $ebayAll->appends(Request::all())->render() !!}</div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    $(document).ready(function () {
        $('.owner-select2').select2({
            placeholder: "Select Owner",
        });
    });
</script>

@endsection