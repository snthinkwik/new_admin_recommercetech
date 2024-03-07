<?php

use Carbon\Carbon;

$statusList = \App\EbayOrders::getAvailableStatusWithKeys();
?>

@extends('app')

@section('title', 'Delivery Settings')

@section('content')

<div class="container">
    <div class="row">
        <div class="col-sm-12">
            <div class="mb-4">
                <a class="btn btn-default" href="{{ session('admin.ebay-orders') ?: route('admin.ebay-orders') }}">Back to list</a>
                <a href="#import-dpd" data-toggle="collapse" class="btn btn-default">Import Delivery Invoices</a>
                <a class="btn btn-default" href="{{ session('admin.delivery-settings.dpd') ?: route('admin.delivery-settings.dpd') }}">DPD Invoice Data</a>
                <a class="btn btn-default" href="{{ session('admin.delivery-settings.dpd.matched') ?: route('admin.delivery-settings.dpd.matched') }}">Match DPD</a>
                <a class="btn btn-default" href="{{ session('admin.missing.delivery.fees') ?: route('admin.missing.delivery.fees') }}">View Missing Delivery Fees</a>
            </div>
        </div>
    </div>
    @include('messages')

    <div id="import-dpd" class="collapse  show-if-has-error mb15">
        @include('delivery-settings.import_dpd')
    </div>
    <div class="row">
        <div class="col-md-5">
            <div class="panel panel-default  ">
                <div class="panel-heading">Add Delivery Settings</div>
                <div class="panel-body">
                    {!! BsForm::open(['method' => 'post', 'route' => 'admin.delivery-settings.save']) !!}
                    <div class="row">

                        <div class="col-sm-12">
                            <div class="form-group">
                                {!! BsForm::groupText('carrier', null, ['required' => 'required']) !!}
                            </div>
                        </div>
                        <div class="col-sm-12">
                            <div class="form-group">
                                {!! BsForm::groupText('service_name', null, ['required' => 'required']) !!}
                            </div>
                        </div>
                        <div class="col-sm-12">
                            <div class="form-group">
                                {!! BsForm::groupText('cost' , null, ['required' => 'required']) !!}
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

        <div class="col-md-7">
            <div id="universal-table-wrapper">
                @include('delivery-settings.list')
            </div>
            <div id="universal-pagination-wrapper">{!! $deliverySettingsList->appends(Request::all())->render() !!}</div>
        </div>
    </div>
</div>

@endsection
