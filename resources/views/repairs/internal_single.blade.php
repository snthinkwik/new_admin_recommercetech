<?php
use Carbon\Carbon;
$totalPurchaseCost=0;
$totalPartsCost=0;
foreach ($repairs->RepaireItemInternal as $repair){

    if($repair->stock){
        $totalPurchaseCost += $repair->stock->total_costs;
        $totalPartsCost +=$repair->stock->part_cost;
    }
}


$status=\App\RepairsItems::STATUS_OPEN;

foreach ($repairs->RepaireItemInternal as $repairItem){
    if($repairItem->status===\App\RepairsItems::STATUS_CLOSE){
        $status=\App\RepairsItems::STATUS_CLOSE;
    }else{
        $status=\App\RepairsItems::STATUS_OPEN;
    }
}

?>
@extends('app')

@section('title', "Internal Repairs - Details")

@section('content')

    <div class="container">

        <div>
            <strong class="text-info">Total Item:{{$openCount+ $closeCount}}</strong>,
            <strong class="text-success">Open:{{$openCount}}</strong>,
            <strong class="text-danger">Close:{{$closeCount}}</strong>
            <br>
            <strong class="text-primary">Total Purchase Cost:{{  money_format(config('app.money_format'), $totalPurchaseCost)  }}</strong><br>
            <strong class="text-primary">Total Parts Cost:{{ money_format(config('app.money_format'), $totalPartsCost)  }}</strong><br>


            <h2> Internal  Repair #{{ $repairs->id }} - Details</h2>

        </div>

        @if(session('message.m_error'))
            <div class="alert alert-danger">
                @foreach(session('message.m_error') as $error)

                    <li>{{$error}}</li>

                @endforeach
            </div>
        @endif

        @include('messages')

        <div class="row">
            <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="panel-heading">Details</div>
                    <div class="panel-body">
                        <table class="table table-bordered table-hover">
                            <tr>
                                <th>ID</th>
                                <td>{{ $repairs->id }}</td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td>{{ $status }}</td>
                            </tr>
                            <tr>
                                <th>Engineer</th>
                                <td>{{ $repairs->repairengineer->name }}</td>
                            </tr>

                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="OriginalFaultsModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Original Repaired Faults</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body" id="originalFaults">

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>

                    </div>
                </div>
            </div>
        </div>

    </div>
    @if(isset($repairs->RepaireItemInternal))
        <table class="table table-striped table-condensed">
            <thead>
            <tr id="item-sort">
                <th name="stock_id">Stock Id</th>
                <th name="item_name">Item Name</th>
                <th>Capacity</th>
                <th>Item Status</th>
                <th>Test Status</th>
                <th>Touch/Face ID Working?</th>
                <th>Cracked Back</th>
                <th>Network</th>
                <th>IMEI/Serial</th>
                <th>Purchase Price</th>
                <th>Original Faults</th>
                <th>Parts</th>
                <th>Total Part Cost</th>
                <th>Total Purchase Price</th>
                <th>Vat Type</th>
                <th name="days_repair">No. Days in Repair</th>
                <th>Status</th>
                <th>Created At</th>
                <th>Closed At</th>
                <th></th>
                <th></th>
            </tr>
            </thead>
            <tbody>

            @foreach($repairs->RepaireItemInternal as $repair)

                @if(!is_null($repair->stock))
                <tr>
                    <td>{{$repair->stock->id}}</td>
                    <td>{{$repair->stock->name}}</td>
                    <td>{{$repair->stock->capacity_formatted}}</td>
                    <td>{{$repair->stock->status}}</td>

                    <td>{{$repair->stock->test_status}}</td>
                    <td>{{$repair->stock->touch_id_working}}</td>
                    <td>{{$repair->stock->cracked_back}}</td>
                    <td>{{$repair->stock->network}}</td>
                    <td>{{$repair->stock->imei!=="" ?$repair->stock->imei:$repair->stock->serial}}</td>
                    <td>{{money_format(config('app.money_format'),$repair->stock->purchase_price)  }}</td>
                     <td>
                        {{ str_limit(strip_tags($repair->original_faults), 30) }}
                        <br>
                        @if (strlen(strip_tags($repair->original_faults)) > 30)
                            <a href="#" data-toggle="modal" data-target="#OriginalFaultsModal" class="originReadMore" data-id="{{$repair->id}}" ><i class="fa fa-eye"></i> </a>
                        @endif
                    </td>
                    <td>{{$repair->parts}}</td>
                    <td>{{ money_format(config('app.money_format'),$repair->internal_repair_cost)  }}</td>
                    <td>{{ money_format(config('app.money_format'),$repair->stock->total_cost_with_repair)  }}</td>
                    <td>{{$repair->stock->vat_type}}</td>
                    <td> {{$repair->no_days}} </td>
                    <td>{{$repair->status}}</td>
                    <td>{{$repair->created_at}}</td>
                    <td>{{$repair->closed_at}}</td>
                    <td>
                        {!! BsForm::open(['method' => 'post', 'route' => 'repairs.close']) !!}
                        {!! BsForm::hidden('id',  $repair->id) !!}
                        {!! BsForm::submit('Close', ['class' => 'confirmed btn-danger', 'data-confirm' => "Are you sure you want to Close this Repair?"]) !!}
                        {!! BsForm::close() !!}
                    </td>
                    <td class="text-danger">
                        {!! BsForm::open(['method' => 'post', 'route' => 'repairs.external.delete']) !!}
                        {!! BsForm::hidden('id',  $repair->id) !!}
                        @if(!in_array($repair->stock->status,[\App\Stock::STATUS_PAID,\App\Stock::STATUS_SOLD]) )
                        {!! BsForm::submit('Delete', ['class' => 'confirmed btn-danger', 'data-confirm' => "Are you sure you want to delete this Data?"]) !!}
                        @endif
                        {!! BsForm::close() !!}

                    </td>
                    <td></td>
                    <td></td>
                </tr>
                @endif
            @endforeach
            </tbody>
        </table>
    @endif

@endsection

@section('scripts')
    <script>


        $(".originReadMore").on('click',function () {
            var id= $(this).data("id")

            $.ajax({
                url: "{{ route('repairs.faults') }}",
                method: 'POST',
                data: {
                    "_token": "{{ csrf_token() }}",
                    id: id,
                },
                success: function (data) {
                    $("#originalFaults").html(data.data.original_faults);

                }
            });

        })






    </script>
@endsection