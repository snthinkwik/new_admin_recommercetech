<?php
use Carbon\Carbon;
$totalPurchaseCost=0;
foreach ($repairs->RepaireItemExternal as $repair){

    if($repair->stock){
        $totalPurchaseCost += $repair->stock->total_costs;
    }
}


                $status=\App\RepairsItems::STATUS_OPEN;

                    foreach ($repairs->RepaireItemExternal as $repairItem){
                        if($repairItem->status===\App\RepairsItems::STATUS_CLOSE){
                            $status=\App\RepairsItems::STATUS_CLOSE;
                        }else{
                            $status=\App\RepairsItems::STATUS_OPEN;
                        }
                    }



?>
@extends('app')

@section('title', "External Repairs - Details")

@section('content')

    <div class="container">

        <div>
            <strong class="text-info">Total Item:{{$openCount+ $closeCount}}</strong>,
            <strong class="text-success">Open:{{$openCount}}</strong>,
            <strong class="text-danger">Close:{{$closeCount}}</strong>
            <br>
            <strong class="text-primary">Total Purchase Cost: {{money_format(config('app.money_format'), $totalPurchaseCost)}} </strong><br>
            <strong class="text-primary">Total Estimate Cost: {{ money_format(config('app.money_format'), $totalCost[0]->total_estimate_cost)   }}</strong><br>
            <strong class="text-warning">Total Actual Cost: {{ money_format(config('app.money_format'), $totalCost[0]->total_actual_repair_cost)   }}</strong><br>

           <h2> External  Repair #{{ $repairs->id }} - Details</h2>

        </div>


        <a class="btn btn-primary mb10" href="{{ route('repairs') }}"><i class="fa fa-reply"></i> Back to list</a>
        <a href="#stock-import" class="btn btn-primary mb10" data-toggle="collapse"><i class="fa fa-download"></i> Import Repair</a>
        <a href="{{route('repairs.external.export',['id'=>$repairs->id])}}" class="btn btn-primary mb10"><i class="fa fa-cloud-upload" aria-hidden="true"></i> Export Repair</a>
        <a href="#" data-toggle="modal" data-target="#addModal" class="btn btn-primary mb10" data-id="{{$repairs->id}}" ><i class="fa fa-plus" aria-hidden="true"></i>  Add New </a>
        <br>

        <div id="stock-import" class="collapse  show-if-has-error mb15">
            <p><a href="#stock-import" data-toggle="collapse">Import stock</a></p>
            <p class="mv20"><a href="{{ route('repairs.download.template') }}">Click here to download a CSV template.</a></p>
            @include('repairs.import-form')
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

            <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">

                <div class="modal-dialog" role="document">
                    {!! BsForm::open(['method' => 'post', 'route' => 'repairs.update.cost']) !!}
                    {!! BsForm::hidden('id', $repairs->id,['id'=>"edit"]) !!}

                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Repaired Faults</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>

                        <div class="modal-body">
                            {!! BsForm::textarea('repaired_faults', null, ['placeholder' => 'repaired_faults','id'=>'faults']) !!}

                        </div>
                        <div class="modal-footer">
                            {!! BsForm::submit('Save') !!}
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>

                        </div>

                    </div>

                    {!! BsForm::close() !!}
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

            <div class="modal fade" id="addModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Add New External Repair</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body" id="faults">
                            <div class="row p5">


                                {!! BsForm::open(['method' => 'post', 'route' => 'repairs.external.add']) !!}
                                {!! BsForm::hidden('repair_id',$repairs->id ) !!}

                                <div class="col-lg-6 col-md-6">
                                <div class="input-group">
                                    <label>RCT Ref</label>
                                    {!! BsForm::text('rct_ref', null, ['placeholder' => 'Enter Rct Ref','id'=>'rct_ref']) !!}
                                </div>
                                </div>
                                <div class="col-lg-6 col-md-6">
                                <div class="input-group">
                                    <label>IMEI/Serial</label>
                                    {!! BsForm::text('imei_serial', null, ['placeholder' => 'Enter IMEI/Serial','id'=>'imei']) !!}
                                </div>
                                </div>
                            </div>
                            <div class="row ">
                                <div class="col-lg-6 col-md-6">
                                    <div class="input-group">
                                        <label> Estimate Repair Cost</label>
                                        {!! BsForm::text('estimate_cost', null, ['placeholder' => 'Enter Estimate Repair Cost']) !!}
                                    </div>
                                </div>
                                <div class="col-lg-6 col-md-6">
                                    <div class="input-group">
                                        <label>Actual Repair Cost</label>
                                        {!! BsForm::text('actual_cost', null, ['placeholder' => 'Enter Actual Repair Cost']) !!}
                                    </div>
                                </div>

                            </div>

                            <div class="row mt-2">
                                <div class="col-lg-12 col-md-12">
                                    <div class="input-group">
                                        <label>Original Faults</label>
                                        {!! BsForm::textarea('original_faults', null, ['placeholder' => 'Enter  Original Faults', 'rows' => 3,'id'=>'original_faults']) !!}
                                    </div>
                                </div>

                            </div>
                            <div class="row p10">
                            {!! BsForm::submit('Add New Repair') !!}
                            {!! BsForm::close() !!}
                            </div>

                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>

                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>
    @if(isset($repairs->RepaireItemExternal))
        <table class="table table-striped table-condensed">
            <thead>
            <tr id="item-sort">
                <th name="stock_id">Stock Id</th>
                <th name="item_name">Item Name</th>
                <th>Item Status</th>
                <th>Capacity</th>
                {{--<th>Test Result</th>--}}
                <th>Test Status</th>
                <th>Touch/Face ID Working?</th>
                <th>Cracked Back</th>
                <th>Network</th>
                <th>IMEI/Serial</th>
                <th>Purchase Price</th>
                <th>Original Faults</th>
                <th>Estimate Repair Cost</th>
                <th>Repaired Faults</th>
                <th>Actual Repair Cost</th>
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
            @foreach($repairs->RepaireItemExternal as $repair)
                @if(!is_null($repair->stock))
                <tr>
                    <td>{{$repair->stock->id}}</td>
                    <td>{{str_replace( array('@rt'), 'GB', $repair->stock->name)  }}</td>
                    <td>{{$repair->stock->status}}</td>
                    <td>{{$repair->stock->capacity_formatted}}</td>
                    {{--<td>--}}
                        {{--<p>{!! $repair->stock->phone_check  ? $repair->stock->phone_check->report_failed_render:'-'!!}</p>--}}
                    {{--</td>--}}
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
                    <td>
                        {!! BsForm::model($repair, ['method' => 'post', 'route' => 'repairs.update.cost']) !!}
                        {!! BsForm::hidden('id', $repair->id) !!}
                        <div class="form-group">
                            <div class="input-group">
                                {!! BsForm::text('estimate_repair_cost',$repair->estimate_repair_cost) !!}
                                <span class="input-group-btn">
										<button type="submit" class="btn btn-primary">
											<i class="fa fa-check"></i>
										</button>

									</span>
                            </div>
                        </div>
                        {!! BsForm::close() !!}
                    </td>
                    <td>

                        {{ str_limit(strip_tags($repair->repaired_faults), 30) }}
                        <br>
                        <a href="#" data-toggle="modal"  data-target="#exampleModal" class="readMore edit" data-id="{{$repair->id}}" ><i class="fa fa-edit"></i> </a>


                    </td>
                    <td>

                        {!! BsForm::model($repair, ['method' => 'post', 'route' => 'repairs.update.cost']) !!}
                        {!! BsForm::hidden('id', $repair->id) !!}
                        <div class="form-group">
                            <div class="input-group">
                                {!! BsForm::text('actual_repair_cost',$repair->actual_repair_cost) !!}
                                <span class="input-group-btn">
										<button type="submit" class="btn btn-primary">
											<i class="fa fa-check"></i>
										</button>

									</span>
                            </div>
                        </div>
                        {!! BsForm::close() !!}

                    </td>

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

                        {{--<a href="{{route('repairs.external.delete',['id'=>$repair->id])}}" onclick=""> <i class="fa fa-trash"></i></a>--}}
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
        $(".readMore").on('click',function () {
           var id= $(this).data("id")

            $.ajax({
                url: "{{ route('repairs.faults') }}",
                method: 'POST',
                data: {
                    "_token": "{{ csrf_token() }}",
                    id: id,
                },
                success: function (data) {
                    $("#faults").html(data.data.repaired_faults);

                }
            });

        })

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

        $("#imei").on('keyup',function () {

            var value=$("#imei").val();

            $("#original_faults").addClass('loadinggif');

            if(value==""){
                $("#original_faults").val("");
            }

            $.ajax({
                url: "{{ route('stock.external.phone-check-result') }}",
                method: 'POST',
                data: {
                    "_token": "{{ csrf_token() }}",
                    value: value,
                },
                success: function (data) {
                  //  $("#originalFaults").html(data.data.original_faults);
                    $("#original_faults").removeClass('loadinggif');
                    if(data.data!==""){
                        $("#original_faults").val(data.data);
                    }




                }
            });

        })

        $("#rct_ref").on('keyup',function () {

            var value=$("#rct_ref").val();

            $.ajax({
                url: "{{ route('stock.external.phone-check-result') }}",
                method: 'POST',
                data: {
                    "_token": "{{ csrf_token() }}",
                    value: value,
                },
                success: function (data) {
                    //  $("#originalFaults").html(data.data.original_faults);
                    $("#original_faults").removeClass('loadinggif');
                    if(data.data!==""){
                        $("#original_faults").val(data.data);
                    }




                }
            });

        })

        $(".edit").on('click',function () {
            var id= $(this).data("id");

            $("#edit").val(id);

        })

    </script>
@endsection