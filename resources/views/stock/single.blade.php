<?php

use App\Models\Stock;
use App\LockCheck;
use App\Models\RepairsItems;

$imeiMessages = session('stock.imei_check_messages');
$productTypes = ['' => 'Please Select'] + Stock::getAvailableProductTypesWithKeys();
$repairIdList = \App\Models\Repair::select('repair_id')->get();


?>
@extends('app')


@section('title',  str_replace(array('@rt'), 'GB', $item->name))

@section("scripts")

    <script type='text/javascript' src='{{ asset("js/image_uploader.js") }}'></script>

@endsection

@section('content')
    <style>
        .margin-bar {
            background-color: #1aa3ff;
            color: #fff;
            text-align: center;
            font-size: 18px;
            padding: 10px 0px;
            margin-bottom: 45px;
        }

        .standard-bar {
            background-color: #5bbd5b;
            color: #fff;
            text-align: center;
            font-size: 18px;
            padding: 10px 0px;
            margin-bottom: 45px;
        }
    </style>

    @if($item->vat_type == 'Margin')
        <div class="margin-bar">This item is VAT Margin</div>
    @else
        <div class="standard-bar">This item is Standard VAT</div>
    @endif
    <div class="container single-stock-product">

        @include('messages')
        <p><a class="btn btn-default" href="{{ session('stock.last_url') ?: route('stock') }}">Back to list</a></p>
        <div class="row">
            <div class="col-md-4">

                @if($item->status == Stock::STATUS_BATCH)
                    {!! BsForm::open(['method' => 'post', 'route' => 'stock.remove-from-batch']) !!}
                    {!! BsForm::hidden('stock', $item->id) !!}
                    {!! BsForm::groupSubmit('Remove From Batch',
                    ['class' => 'confirmed',
                    'data-confirm' => 'Are you sure you want to remove this item from batch?']) !!}
                    {!! BsForm::close() !!}
                @endif

                @if($item->status == Stock::STATUS_LOST)
                    {!! BsForm::open(['method' => 'post', 'route' => 'stock.move-to-stock']) !!}
                    {!! BsForm::hidden('id', $item->id) !!}
                    {!! BsForm::groupSubmit('Remove from Lost Mode',
                    ['class' => 'confirmed', 'data-confirm' => 'Item will be moved to Stock']) !!}
                    {!! BsForm::close() !!}
                @endif

                @if($item->status == Stock::STATUS_DELETED)
                    {!! BsForm::open(['method' => 'post', 'route' => 'stock.delete-permanently']) !!}
                    {!! BsForm::hidden('id', $item->id) !!}
                    {!! BsForm::groupSubmit('Permanently delete this item',
                    ['class' => 'confirmed', 'data-confirm' => 'Item will be permanently removed']) !!}
                    {!! BsForm::close() !!}
                @endif

                @if(Auth::user()->type == 'admin' && in_array(Auth::user()->admin_type, ['admin', 'manager']))
                    @if($item->unlock)
                        @if($item->unlock->status != "Unlocked")
                            <p class="text-info"><b>Unlock Status: </b> {{ $item->unlock->status }}</p>
                        @endif
                    @elseif(!$item->imei)
                        <p class="text-info">IMEI is required to unlock this Phone</p>
                    @endif
                @endif

                @if ($imeiMessages)
                    @foreach ($imeiMessages as $imeiMessage)
                        <p class="p5 bg-{{ $imeiMessage['htmlClass'] }}">{{ $imeiMessage['text'] }}</p>
                    @endforeach
                @endif

                @include('stock.form')
            </div>
            <div class="col-md-8">
                <div class="row">
                    <div class="col-md-offset-10">
                        @if($item->status===Stock::STATUS_IN_STOCK)
                            <a href="{{route('stock.single.phone-check',['id'=>$item->id])}}" class="btn btn-primary">Phone
                                Check</a>
                        @endif
                    </div>

                    <div class="col-md-6">
                        @if($item->status == Stock::STATUS_INBOUND)
                            {!! BsForm::open(['route' => 'stock.item-receive', 'method' => 'post', 'class' => '']) !!}
                            {!! BsForm::hidden('stock_id', $item->id) !!}
                            {!! BsForm::button('Receive Item',
                            ['type' => 'submit',
                            'class' => 'btn btn-default btn-block confirmed',
                            'data-toggle' => 'tooltip', 'title' => "Receive Item", 'data-placement'=>'bottom',
                            'data-confirm' => "Are you sure you want to receive this item?"])
                            !!}
                            {!! BsForm::close() !!}

                            {!! BsForm::open(['route' => 'stock.item-delete', 'method' => 'post', 'class' => '']) !!}
                            {!! BsForm::hidden('stock_id', $item->id) !!}
                            {!! BsForm::button('Delete Item',
                            ['type' => 'submit',
                            'class' => 'btn btn-danger btn-block confirmed',
                            'data-toggle' => 'tooltip', 'title' => "Delete Item", 'data-placement'=>'bottom',
                            'data-confirm' => "Are you sure you want to delete this item?"])
                            !!}
                            {!! BsForm::close() !!}
                        @endif
                    </div>
                </div>

                @if($item->failed_mdm)
                    <div class="alert alert-danger"><h2>ITEM TO BE RETURNED TO SUPPLIER</h2></div>
                @endif

                @if($item->phone_check)
                    <div class="panel panel-default">

                        <div class="panel-body">
                            <p><b>Phone Diagnostics</b></p>
                            <p>{!! $item->phone_check->report_render !!}</p>

                            <a class="btn btn-xs btn-default" data-toggle="collapse" data-target="#report-raw">View Raw
                                Data</a>
                            <div class="collapse" id="report-raw">
                                {!! $item->phone_check->response_render !!}
                            </div>
                        </div>
                    </div>
                @endif

                <div class="row">
                    <div class="col-sm-3">
                        <h5><b>RCT Ref:</b> {{ $item->our_ref }}</h5>
                    </div>
                    @if(isset($item->product->non_serialised))
                        <div class="col-sm-4">
                            <h5><b>Product Qty:</b> {{ number_format($item->product->multi_quantity) }}</h5>
                        </div>
                    @endif
                    <div class="col-sm-3">
                        <h5>No. Tests: {{ $item->phone_check_updates }}</h5>
                    </div>
                    <div class="col-sm-1">
                        @if ($item->purchase_country)
                            <img src="{{ asset('/img/stripe-flag-set/' . $item->purchase_country) . '.png' }}">
                        @endif
                    </div>
                </div>


                {{--@if($repairsInternalCount > 0)--}}

                {{--<a class="btn btn-default btn-block" data-toggle="collapse" data-target="#repair-log">Repair Log</a>--}}
                {{--<div class="panel panel-default collapse @if(Request::get('page')) in @endif" id="repair-log">--}}
                {{--<div class="panel-body">--}}
                {{--<table class="table table-bordered">--}}
                {{--<thead>--}}
                {{--<tr>--}}
                {{--<th>User</th>--}}
                {{--<th>Log</th>--}}
                {{--<th>Date</th>--}}
                {{--</tr>--}}
                {{--</thead>--}}
                {{--<tbody>--}}
                {{--@forelse($repairLogs as $log)--}}
                {{--<tr>--}}
                {{--<td>@if($log->user)<a--}}
                {{--href="{{ route('admin.users.single', ['id'=>$log->user_id]) }}">{{ $log->user->full_name }}</a>@else--}}
                {{--- @endif</td>--}}
                {{--<td class="word-break-all">{!! nl2br($log->content) !!}</td>--}}
                {{--<td>{{ $log->created_at->format("d M Y H:i:s") }}</td>--}}
                {{--</tr>--}}
                {{--@empty--}}
                {{--<tr>--}}
                {{--<td colspan="3">No record found</td>--}}
                {{--</tr>--}}
                {{--@endforelse--}}
                {{--</tbody>--}}
                {{--</table>--}}
                {{--{!! $logs->appends(Request::all())->render() !!}--}}
                {{--</div>--}}
                {{--</div>--}}

                {{--@endif--}}

                <a class="btn btn-default btn-block" data-toggle="collapse" data-target="#stock-log">Log</a>
                <div class="panel panel-default collapse @if(Request::get('page')) in @endif" id="stock-log">
                    <div class="panel-body">
                        <table class="table table-bordered">
                            <thead>
                            <tr>
                                <th>User</th>
                                <th>Log</th>
                                <th>Date</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($logs as $log)
                                <tr>
                                    <td>@if($log->user)
                                            <a
                                                    href="{{ route('admin.users.single', ['id'=>$log->user_id]) }}">{{ $log->user->full_name }}</a>
                                        @else
                                            -
                                        @endif</td>
                                    <td class="word-break-all">{!! nl2br(str_replace("to Sold","to sold",$log->content)) !!}</td>
                                    <td>{{ $log->created_at->format("d M Y H:i:s") }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                        {!! $logs->appends(Request::all())->render() !!}
                    </div>
                </div>


                <a class="btn btn-default btn-block" data-toggle="collapse" data-target="#network-check">Network
                    Check</a>
                <div class="panel panel-default collapse" id="network-check">
                    <div class="panel-body">
                        @if(count($item->network_checks))
                            @foreach($item->network_checks as $network_check)
                                <p>Network Check Status: {{ ucfirst($network_check->status) }} <span class="pull-right"><i
                                                class="fa fa-calendar"></i> {{ $network_check->created_at->format('d/m/Y, H:i:s') }}</span>
                                </p>
                                <p>{!!  str_replace('<div>', '', str_replace("<\/font>", "</font>", $network_check->response)) !!}</p>
                            @endforeach
                        @else
                            <p class="text-info">Network Check has not been placed yet.</p>
                        @endif

                        <hr/>

                        @if($item->imei)
                            {!! BsForm::open(['route' => 'mobicode.gsx-check', 'id' => 'mobicode-gsx-check', 'method' => 'post']) !!}
                            {!! BsForm::hidden('stock_id', $item->id) !!}
                            {!! BsForm::groupSubmit('Check Network', ['class'=>'btn-sm']) !!}
                            {!! BsForm::close() !!}
                        @else
                            <p class="text-info">Please add IMEI to check the network</p>
                        @endif
                    </div>
                </div>

                <?php
                $colour = $item->product_id ? "background-color:#3CB371" : "background-color:#FA8072"
                ?>

                <a class="btn btn-default btn-block" data-toggle="collapse" data-target="#product-mapping"
                   style=" {{$colour}}">Product
                    Mapping</a>
                <div class="panel panel-default collapse" id="product-mapping">
                    <div class="panel-body">
                        @if($item->product_id)
                            <h4>
                                <a href="{{ route('products.single', ['id' => $item->product_id]) }}">{{ $item->product->product_name .' '. $item->product_id   }}</a>
                            </h4>
                            @if($item->product->image)
                                <a class="btn btn-sm btn-info mb5" data-toggle="collapse" data-target="#product-image">Image</a>
                                <div class="collapse" id="product-image">
                                    <img src="{{ $item->product->image }}" alt="No Image" class="img-thumbnail"/>
                                </div>
                            @endif

                            {!! BsForm::open(['method' => 'post', 'route' => 'stock.remove-product-assignment']) !!}
                            {!! BsForm::hidden('id', $item->id) !!}
                            {!! BsForm::groupSubmit('Remove Assignment', ['class' => 'btn btn-sm btn-block btn-danger']) !!}
                            {!! BsForm::close() !!}
                        @else
                            {!! BsForm::open(['method' => 'post', 'route' => 'stock.assign-product', 'id' => 'stock-assign-product-form']) !!}
                            {!! BsForm::hidden('stock_id', $item->id) !!}
                            <div class="form-group">
                                <div class="input-group">
                                    <span class="input-group-addon">Recomm Product</span>
                                    {!! BsForm::text('trg_product', null, ['placeholder' => 'search']) !!}
                                    <span class="input-group-btn">
                                    {!! BsForm::submit('Save') !!}
                                </span>
                                </div>
                            </div>
                            {!! BsForm::hidden('product_id') !!}
                            {!! BsForm::groupSubmit('Assign Product', ['class' => 'btn-block']) !!}
                            {!! BsForm::close() !!}
                        @endif
                    </div>
                </div>


                <a class="btn btn-default btn-block" data-toggle="collapse" data-target="#show-advanced">Show
                    Advanced</a>
                <div class="panel panel-default collapse" id="show-advanced">
                    <div class="d-flex m-2">

                        @if($item->unlock)
                            @if($item->unlock->status != "Unlocked")
                                <p class="text-info"><b>Unlock Status: </b> {{ $item->unlock->status }}</p>
                            @endif

                        @elseif(!$item->imei)
                            <p class="text-info">IMEI is required to unlock this Phone</p>
                        @elseif(!in_array ( $item->status, [Stock::STATUS_INBOUND ,Stock::STATUS_LISTED_ON_AUCTION]))
                            {!! BsForm::open(['method' => 'post', 'route' => 'unlocks.add-as-admin']) !!}
                            {!! BsForm::hidden('imeis[]', $item->imei) !!}
                            {!! BsForm::hidden('network', $item->network) !!}
                            {!! BsForm::submit('Unlock this Phone') !!}
                            {!! BsForm::close() !!}
                        @endif
                        @if(!$repairsInternalCount)
                            <a data-toggle="collapse" data-target="#add-repair" class="btn btn-primary ml-2">Create a
                                Repair</a>
                        @endif
                    </div>

                    @if(!$repairsInternalCount)
                        <div id="add-repair" class="collapse mt5">
                            <div class="panel-heading">Create Repair</div>
                            {!! BsForm::open(['method' => 'post', 'route' => 'stock.repair.add', 'class'=> 'm-2']) !!}
                            <div class="m-3">
                                <div class="row">
                                    <div class="col-sm-12 col-md-4">
                                        <div class="input-group">
                                            <span class="input-group-addon">Type</span>
                                            {!! BsForm::select('type', $repairTypes, null, ['required' => 'required']) !!}
                                        </div>
                                    </div>
                                    <div class="col-sm-12 col-md-4">
                                        <div class="input-group">
                                            <span class="input-group-addon">Engineer</span>
                                            {!! BsForm::select('engineer', $repairEngineers, null, ['required' => 'required']) !!}
                                        </div>
                                    </div>
                                    <div class="col-sm-12 col-md-4">
                                        <div class="input-group">
                                            <span class="input-group-addon">Status</span>
                                            {!! BsForm::select('status', $repairStatus, null, ['required' => 'required']) !!}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <br>
                            {!! BsForm::hidden('item_id', $item->id) !!}
                            {!! BsForm::submit('Create Repair',['class'=>'btn btn-primary ml-3']) !!}
                            {!! BsForm::close() !!}
                        </div>
                    @endif

                    <div class="panel panel-default" id="repairs">
                        <div class="panel-heading">Internal Repairs <span
                                    class="badge">{{ count($item->repair_item) }}</span>
                        </div>
                        <div class="panel-body">
                            <a class="btn btn-default btn-xs" data-toggle="collapse" data-target="#repairs-and-parts"><i
                                        class="fa fa-bars"></i> Details</a>
                            <div class="well collapsee" id="repairs-and-parts">
                                {!! json_encode($item->getRepairsAndParts()) !!}
                            </div>
                            @if(!count($item->repair_item))
                                <div class="alert alert-info">No Repairs</div>
                            @else
                                <table class="table table-bordered table-hover">
                                    <tr>
                                        <th>Repaire Id</th>
                                        <th>Type</th>
                                        <th>Engineer</th>
                                        <th>Status</th>
                                        <th>Parts</th>
                                        <th>Total Repair Cost</th>
                                        <th>Open Date</th>
                                        <th>Closed Date</th>
                                    </tr>

                                    @foreach($item->repair_item as $repair)
                                        @if($repair->type===RepairsItems::TYPE_INTERNAL)
                                            <tr>

                                                <td>
                                                    <a href="{{route('repairs.single',['id'=>$repair->repair_id])}}"> {{ $repair->repair_id }}</a>
                                                </td>

                                                <td>{{ $repair->repair->repairType->name }}</td>

                                                <td>{{ $repair->repair->repairEngineer->name }}</td>

                                                <td>{{ $repair->status }}</td>


                                                <td>@if(isset($repair->parts))
                                                        {{ $repair->parts }}
                                                    @endif</td>

                                                <td>
{{--                                                    {{ money_format(config('app.money_format'),$item->part_cost )  }}--}}
                                                {{$item->part_cost}}
                                                </td>

                                                <td> @if($repair->created_at)
                                                        {{ $repair->created_at->format('d/m/y H:i:s') }}
                                                    @endif</td>
                                                <td>{{ $repair->closed_at ? $repair->closed_at : '-' }}</td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </table>
                            @endif
                        </div>

                        <div class="panel panel-default" id="parts-used">
                            <div class="panel-heading">Parts Used</div>
                            <div class="panel-body">
                                <div id="repair-parts-list-wrapper">
                                    @if(count($item->parts))
                                        <table class="table table-bordered table-hover">
                                            <tr>
                                                <th>ID</th>
                                                <th>Part Name</th>
                                                <th>Cost</th>
                                                <th class="text-center"><i class="fa fa-calendar"></i></th>
                                                <th class="text-center text-danger"><i class="fa fa-remove"></i></th>
                                            </tr>
                                            @foreach($item->stock_parts as $stockPart)
                                                <tr>
                                                    <td>{{ $stockPart->part->id }}</td>
                                                    <td>{{ $stockPart->part->name }}</td>
                                                    <td>{{ $stockPart->cost_formatted }}</td>
                                                    <td>{{ $stockPart->created_at_formatted ? $stockPart->created_at->format('d/m/y') : '' }}</td>
                                                    <td>
                                                        {!! BsForm::open(['method' => 'post', 'route' => 'stock.parts-remove']) !!}
                                                        {!! BsForm::hidden('stock_id', $item->id) !!}
                                                        {!! BsForm::hidden('stock_part_id', $stockPart->id) !!}
                                                        {!! BsForm::button("<i class='fa fa-remove'></i>",
                                                        [
                                                        'type' => 'submit',
                                                        'class' => 'btn-xs btn-danger btn-block confirmed',
                                                        'data-confirm' => 'Part Cost will be recalculated'
                                                        ]) !!}
                                                        {!! BsForm::close() !!}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </table>
                                        <hr/>
                                    @else
                                        <p class="text-info">No Parts</p>
                                    @endif
                                </div>
                                <a data-toggle="collapse" data-target="#add-parts" class="btn btn-default"><i
                                            class="fa fa-bars"></i> Add parts</a>
                                <div id="add-parts" class="collapse mt5">
                                    {!! BsForm::open(['id' => 'parts-form']) !!}
                                    {!! BsForm::hidden('stock_id', $item->id) !!}
                                    <div class="input-group">
                                        <span class="input-group-addon">Part</span>
                                        {!! BsForm::text('part_id', null, ['placeholder' => 'Type to select', 'id' => 'parts-search-input']) !!}
                                    </div>
                                    {!! BsForm::close() !!}
                                    <hr/>
                                    <p>Selected Parts</p>
                                    {!! BsForm::open(['id' => 'parts-add-form', 'route' => 'stock.parts-add']) !!}
                                    {!! BsForm::hidden('stock_id', $item->id) !!}
                                    <table class="table table-bordered table-hover" id="parts-add-form-parts-list">
                                        <tr>
                                            <th>ID</th>
                                            <th>Part Name</th>
                                            <th class=text-center>Source</th>
                                            <th class="text-center"><i class='fa fa-remove'></i></th>
                                        </tr>
                                    </table>
                                    {!! BsForm::submit('Add Parts') !!}
                                    {!! BsForm::close() !!}
                                </div>
                            </div>
                        </div>


                    </div>


                    <div class="panel panel-default" id="repairs">


                        <a data-toggle="collapse" data-target="#external-add-repair"
                           class="btn btn-primary ml-2 p-2 m-2">Create External Repair</a>
                        <div id="external-add-repair" class="collapse mt5">

                            <?php

                            $repairItemId = NULL;
                            $repairId = NULL;
                            $estimateCost = NULL;
                            foreach ($item->repair_item as $repair) {
                                if ($repair->type === RepairsItems::TYPE_EXTERNAL) {
                                    $repairItemId = $repair->id;
                                    $repairId = $repair->repair_id;
                                    $estimateCost = $repair->estimate_repair_cost;
                                }
                            }




                            ?>

                            <div class="panel-heading"><h5><strong>Create External Repair</strong></h5></div>
                            {!! BsForm::open(['method' => 'post', 'route' => 'stock.external.repair', 'class'=> 'm-2']) !!}
                            <div class="m-5">
                                <div class="row">

                                    <input type="hidden" value="{{ $repairItemId }}" name="repair_item_id">
                                    <div class="col-sm-12 col-md-12">
                                        <div class="input-group m-2">
                                            <label>Repair Id</label><br>
                                            <select class="repair-select2 form-control" name="id"
                                                    style="width: 341px !important;">
                                                <option value=""></option>
                                                @foreach($repairIdList as $data)

                                                    <option value="{{$data->id}}"
                                                            @if($repairId==$data->id) selected="selected" @endif >{{$data->id}}</option>

                                                @endforeach
                                            </select>
                                        </div>

                                    </div>

                                    <div class="col-sm-6 col-md-6">
                                        <div class="input-group m-2">
                                            <label>Estimate Repair Cost</label>
                                            {!! BsForm::text('estimate_repair_cost',$estimateCost, null, ['required' => 'required']) !!}
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <br>
                            {!! BsForm::hidden('item_id', $item->id) !!}
                            {!! BsForm::submit('Create External Repair',['class'=>'btn btn-primary ml-3',   !$item->repair_item()->count()?'disabled':'' ]) !!}
                            {!! BsForm::close() !!}
                        </div>

                        <div class="panel-heading">External Repairs <span
                                    class="badge">{{ $repairsExternalCount }}</span>
                        </div>
                        <div class="panel-body">
                            <div class="panel-body">


                                @if(!$repairsExternalCount)
                                    <div class="alert alert-info">No Repairs</div>
                                @else
                                    <table class="table table-bordered table-hover">
                                        <tr>
                                            <th>Repair Id</th>
                                            <th>Type</th>
                                            <th>Engineer</th>
                                            <th>Status</th>
                                            <th>Original Faults</th>
                                            <th>Estimate Repair Cost</th>
                                            <th>Repaired Faults</th>
                                            <th>Actual Repair Cost</th>

                                            <th>Open Date</th>
                                            <th>Closed Date</th>
                                        </tr>
                                        @foreach($item->repair_item as $repair)

                                            @if($repair->type===RepairsItems::TYPE_EXTERNAL)

                                                <tr>
                                                    <td>{{ $repair->repair_id }}</td>
                                                    <td>{{ $repair->type }}</td>
                                                    <td>{{$repair->repair->repairEngineer->name}}</td>
                                                    <td>{{$repair->status}}</td>
                                                    <td>{{$repair->original_faults}}</td>
                                                    <td>{{$repair->estimate_repair_cost}}</td>
                                                    <td>{{$repair->repaired_faults}}</td>
                                                    <td>{{$repair->actual_repair_cost}}</td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    </table>
                                @endif
                            </div>
                        </div>
                    </div>


                    <div class="panel panel-default">
                        <div class="panel-heading">Product Type</div>
                        <div class="panel-body">
                            {!! BsForm::model($item, ['method' => 'post', 'route' => 'stock.change-product-type']) !!}
                            {!! BsForm::hidden('id', $item->id) !!}
                            <div class="form-group">
                                <div class="input-group">
                                    <span class="input-group-addon">Product Type</span>
                                    {!! BsForm::select('product_type', $productTypes, null, ['required' => 'required']) !!}
                                    <span class="input-group-btn">{!! BsForm::button("<i class='fa fa-check'></i>", ['type' => 'submit']) !!}</span>
                                </div>
                            </div>
                            {!! BsForm::close() !!}
                        </div>
                    </div>
                </div>

                @if($item->sold)
                    {!! BsForm::open(['route' => 'sales.remove-item']) !!}
                    {!! BsForm::hidden('ref', $item->third_party_ref) !!}
                    {!! BsForm::groupSubmit('Remove from Sale', ['class' => 'btn-block btn-danger confirmed', 'data-confirm' => 'Are you sure you want to remove this item from sale?']) !!}
                    {!! BsForm::close() !!}
                @endif

                <a class="btn btn-default btn-block" data-toggle="collapse" data-target="#sale-history">Sale History</a>
                <div id="sale-history" class="panel panel-default collapse">
                    <div class="panel-body">
                        @if($item->sale_history()->count() > 0)
                            <table class="table table-hover table-bordered">
                                <tr>
                                    <th>Sale ID</th>
                                    <th>Status</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                </tr>
                                @foreach($item->sale_history as $saleHistory)
                                    <tr>
                                        <td>
                                            <a href="{{ route('sales.single', ['id' => $saleHistory->id]) }}">{{ $saleHistory->id }}</a>
                                        </td>
                                        <td>{{ ucfirst($saleHistory->invoice_status_alt) }}</td>
                                        <td>

                                            <a href="{{ route('admin.users.single', ['id' => $saleHistory->user->id]) }}">{{ $saleHistory->user->company_name }}</a>
                                        </td>
                                        <td>{{ $saleHistory->created_at->format('d/m/y H:i:s') }}</td>
                                    </tr>
                                @endforeach
                            </table>
                        @elseif(count($ebaySalesHistory) > 0)
                            <table class="table table-bordered">
                                <thead>
                                <tr>
                                    <th>Order No</th>
                                    <th>Price</th>
                                    <th>Customer</th>
                                    <th>Order Status</th>
                                    <th>Date</th>
                                    <th>Delete</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($ebaySalesHistory as $log)
                                    <tr>
                                        <td>
                                            <a href="{{route('admin.ebay-orders.view',['id' => $log->master_ebay_order_id])}}">{{ $log->order_no}}</a>
                                        </td>
                                        <td>{{ money_format(config('app.money_format'), $log->price) }}</td>
                                        <td class="word-break-all">{{$log->customer}}</td>
                                        <td>
                                            @if($log->order)
                                                {{ucfirst($log->order->status)}}
                                            @endif
                                        </td>
                                        <td>{{ $log->created_at->format("d M Y H:i:s") }}</td>
                                        <td>
                                            <a href="{{route('stock.ebay-remove-sales',['id' => $log->id])}}"> <i
                                                        class="fa fa-times btn btn-danger"></i></a>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="alert alert-info">Nothing Found</div>
                        @endif

                        @if($item->status==Stock::STATUS_SOLD)
                            <div class="alert alert-info">Item is Sold, click here to remove from <a
                                        href="{{route('stock.status.update',['id'=>$item->id])}}"> sale and return to
                                    stock</a></div>
                        @endif
                    </div>
                </div>

                <a class="btn btn-default btn-block" data-toggle="collapse" data-target="#pricing">Pricing</a>
                <div id="pricing" class="panel panel-default collapse">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-7">

                                <div class="form-group">
                                    <label>Sales Price</label>
                                    <div class="input-group">
                                        <span class="input-group-addon">&pound;</span>
                                        {!! BsForm::text('sale_price', number_format($item->sale_price,2), ['disabled' => 'disabled']) !!}
                                    </div>
                                </div>
                                @if($item->vat_type === "Standard")
                                    <?php
                                    $total_price_ex_value = ($item->sale_price / 1.2);
                                    $vat = ($item->sale_price - $total_price_ex_value)
                                    ?>
                                    <div class="form-group">
                                        <label>Vat</label>
                                        <div class="input-group">
                                            <span class="input-group-addon">&pound;</span>
                                            {!! BsForm::text('sale_vat', number_format($vat,2), ['disabled' => 'disabled']) !!}
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label>Total Price ex VAT</label>
                                        <div class="input-group">
                                            <span class="input-group-addon">&pound;</span>
                                            {!! BsForm::text('total_price_ex_vat',number_format($total_price_ex_value,2), ['disabled' => 'disabled']) !!}
                                        </div>
                                    </div>
                                @endif

                                <div class="form-group">
                                    <label data-toggle="tooltip" title="Total Costs">Purchase Price</label>
                                    <div class="input-group">
                                        <span class="input-group-addon">&pound;</span>

                                        {!! BsForm::text('purchase_price',number_format($item->purchase_price,2), ['disabled' => 'disabled']) !!}
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label data-toggle="tooltip" title="Total Costs">Profit</label>
                                    <div class="input-group">
                                        <span class="input-group-addon">&pound;</span>

                                        {!! BsForm::text('profit', $item->profit, ['disabled' => 'disabled']) !!}
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label data-toggle="tooltip" title="Total Costs">True Profit</label>
                                    <div class="input-group">
                                        <span class="input-group-addon">&pound;</span>

                                        {!! BsForm::text('true_profit', $item->true_profit, ['disabled' => 'disabled']) !!}
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Internal Repair Cost</label>
                                    <div class="input-group">
                                        <span class="input-group-addon">&pound;</span>
                                        {!! BsForm::text('part_cost', $item->part_cost, ['disabled' => 'disabled']) !!}
                                    </div>
                                </div>
                                <?php
                                $cost = 0;
                                if (count($item->repair_item) > 0) {
                                    foreach ($item->repair_item as $repair) {
                                        if ($repair->type === RepairsItems::TYPE_EXTERNAL) {
                                            $cost = $repair->actual_repair_cost > 0 ? $repair->actual_repair_cost : $repair->estimate_repair_cost;
                                        }

                                    }


                                }
                                ?>
                                <div class="form-group">
                                    <label>External Repair Cost</label>
                                    <div class="input-group">
                                        <span class="input-group-addon">&pound;</span>
                                        {!! BsForm::text('part_cost',number_format($cost,2), ['disabled' => 'disabled']) !!}
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Unlock Cost</label>
                                    <div class="input-group">
                                        <span class="input-group-addon">&pound;</span>
                                        {!! BsForm::text('unlock_cost', $item->unlock_cost, ['disabled' => 'disabled']) !!}
                                    </div>
                                </div>


                                <div class="form-group">
                                    <label>Total
                                        Costs:</label>
                                    <div class="input-group">
                                        <span class="input-group-addon">&pound;</span>
                                        {!! BsForm::text('Total Costs', number_format($item->total_cost_with_repair,2), ['disabled' => 'disabled']) !!}
                                    </div>
                                </div>


                                {{--<p data-toggle="tooltip"--}}
                                {{--title="{{ $item->purchase_price_formatted }} + {{ $item->part_cost_formatted }} + {{ $item->unlock_cost_formatted }}">--}}
                                {{--<b>Total--}}
                                {{--Costs:</b> {{ money_format(config('app.money_format'), $item->total_cost_with_repair) }}--}}
                                {{--</p>--}}
                            </div>
                            <div class="col-md-5">


                                @if($item->vat_type==="Margin")
                                    <h3 class="mt100">VAT MARGIN</h3>
                                @else
                                    <h3 class="mt100">VAT Standard</h3>

                                @endif
                                @if($item->sold)

                                    <h4 class="mt100">Gross Profit: {{ $item->profit }}</h4>
                                    <h4>VAT: {{ $item->vat_formatted }}</h4>

                                    @if($item->vat_type==="Margin")

                                        <h4>Net
                                            Profit: {{  money_format(config('app.money_format'), $item->profit - $item->marg_vat)  }}</h4>
                                    @else
                                        <h4>Net Profit: {{ $item->true_profit }}</h4>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                </div>


                <a class="btn btn-default btn-block" data-toggle="collapse" data-target="#image_processing">Image
                    Processing</a>
                <div id="image_processing" class="panel panel-default collapse">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-7">


                                {!! BsForm::open(['method' => 'post', 'route' => 'upload.processing-image', 'files' => 'true']) !!}

                                <input type="hidden" value="{{$item->id}}" name="stock_id">

                                <div class="panel-body">
                                    F
                                    @if(count($item->processingImage)>0)

                                        <div class="row">
                                            @foreach($item->processingImage as $image)
                                                <div class="col-md-4 col-xs-12 p10">

                                                    <a href="{{asset("/img/processing-image/".$image->image_path)}}"
                                                       target="_blank">
                                                        <img src="{{asset("/img/processing-image/".$image->image_path)  }}"
                                                        class="img-responsive" width="100" height="100">
                                                    </a>
                                                    <a href="{{route('delete.processing-image',['id'=>$image->id])}}"><i
                                                                class="fa fa-trash-o" aria-hidden="true"></i>
                                                    </a>
                                                </div>

                                            @endforeach
                                        </div>

                                    @endif

                                    <div class="row d-flex">
                                        <div class="col-md-8">
                                            <input type="file" name="image[]" class="form-control" multiple/>
                                        </div>
                                        <div class="col-md-4">
                                            {!! BsForm::submit('Upload', ['class' => 'btn btn-primary btn-block']) !!}
                                        </div>
                                    </div>

                                    <div class="row">

                                    </div>
                                </div>

                                {!! BsForm::close() !!}
                            </div>
                        </div>
                    </div>
                </div>


                @if($item->phoneCheckReports)
                    <a class="btn btn-default btn-block" data-toggle="collapse" data-target="#download">PhoneCheck
                        Certificate</a>
                    <div id="download" class="panel panel-default collapse">
                        <div class="panel-body">
                            <div class="row">
                                <div class="col-md-7">
                                    <a href="{{route('phone-check.report',['id'=>$item->phoneCheckReports->id])}}">
                                        Download Test Results Certificate </a>
                                </div>

                                <div class="col-md-7">
                             <a href="{{route('phone-check.eraser.report',['id'=>$item->phoneCheckReports->id])}}">
                                        Download Data Erasure Certificate </a>
                                </div>


                            </div>
                        </div>
                    </div>

            </div>
            @endif
        </div>
    </div>
@endsection

@section('bottom-left')
    @if(\Cache::has('stock-item-'.$item->id) && (preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i',$_SERVER['HTTP_USER_AGENT']) || preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',substr($_SERVER['HTTP_USER_AGENT'],0,4))))
        <div class="bottom-left">
            <div class="alert alert-info">
                {{ \Cache::get('stock-item-'.$item->id) }} people are looking at this right now!
            </div>
        </div>
    @endif
@endsection

@section('nav-right')
    @if(Auth::user()->type == 'admin' && in_array(Auth::user()->admin_type, ['admin', 'manager']))
        <div class="navbar-form navbar-right pr0">
            <div class="btn-group">
                <button id="create-sale" class="btn btn-default">
                    {{ Auth::user() ? Auth::user()->texts['sales']['create'] : 'Create sale' }}
                </button>
            </div>
        </div>
    @endif
    <div id="basket-wrapper" class="navbar-right pr0">
        @include('basket.navbar')
    </div>
@endsection




