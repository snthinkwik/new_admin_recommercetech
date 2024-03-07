<?php

use App\Colour;

$pleaseSelect = ["" => "--Please Select--"];
$colours = $pleaseSelect + array_combine(Colour::orderBy('pr_colour')->lists('pr_colour'), Colour::orderBy('pr_colour')->lists('pr_colour'));
$type = $pleaseSelect + \App\Inventory::getTypeWithKeys();
//$locations = $pleaseSelect + array_combine(\App\Locations::orderBy('name')->lists('id'), \App\Locations::orderBy('name')->lists('name'));
?>
@extends('app')

@section('title', 'Inventory')

@section('content')

    <div class="container single-stock-product">
        <a href="{{route('inventory.index')}}" class="btn btn-success">Back</a>


        {{--@include('inventory.nav')--}}
        @include('messages')
        {!! BsForm::open(['method' => 'post', 'route' => 'inventory.save']) !!}
        <div class="row">
            <div class="col-md-6">
                <div class="row">
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label for="item-name">Product Name</label>
                            {!! BsForm::text('name',isset($inventory)? $inventory->product_name:null,null,['required' => 'required','id'=>'type_add']) !!}
                            {!! BsForm::hidden('id',isset($inventory)? $inventory->id:null) !!}

                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label for="item-name">Type</label>
                            {!! BsForm::select('type',$type,isset($inventory)? $inventory->type:null,['required' => 'required','id'=>'type_add']) !!}


                        </div>
                    </div>

                </div>
                <div class="row">
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label for="item-name">Model</label>
                            {!! BsForm::text('model',isset($inventory)? $inventory->model:null,['required' => 'required']) !!}

                        </div>
                    </div>


                    <div class="col-lg-6">
                        <div class="form-group">
                            <label for="item-name">SKU</label>
                            {!! BsForm::text('sku',isset($inventory)? $inventory->sku:null,['required' => 'required']) !!}

                        </div>
                    </div>

                </div>
                <div class="row" style="" id="capacity">

                    <div class="col-lg-6">
                        <div class="form-group">
                            <label for="item-name">Make</label>
                            {!! BsForm::text('make',isset($inventory)? $inventory->make:null,['required' => 'required']) !!}

                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="form-group">
                            <label for="item-name">Quantity In Stock</label>
                            {!! BsForm::number('quantity_in_stock',isset($inventory)? $inventory->quantity_in_stock:null,['required' => 'required']) !!}

                        </div>
                    </div>


                </div>
                <div class="row">

                    <div class="col-lg-6">
                        <div class="form-group">
                            <label for="item-name">Capacity</label>
                            {!! BsForm::text('capacity',isset($inventory)? $inventory->capacity:null) !!}

                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label for="item-name">Colour</label>
                            {!! BsForm::select('colour',$colours,isset($inventory)? $inventory->colour:null,['id'=>'colour','class'=>'colourSelect2']) !!}
                            {{--<a href="{{route('inventory.manage-colour')}}">Add new Colour</a>--}}
                        </div>
                    </div>

                </div>

                <div class="row">

                    <div class="col-lg-6">
                        <div class="form-group">
                            <label for="item-name">Location</label>
                            {!! BsForm::text('location',isset($inventory)? $inventory->location:null,null,['required' => 'required']) !!}

                        </div>
                    </div>

                </div>

                <div class="row">
                    <div class="col-lg-12 ">
                        <?php
                        if(isset($inventory)){
                            $btnName='Update Inventory';
                        }else{
                            $btnName='Add Inventory';
                        }

                        ?>
                        <div class="form-group">
                            {!! BsForm::groupSubmit($btnName, ['class' => 'btn-sm btn-block']) !!}
                        </div>
                    </div>
                </div>
            </div>
            @if(isset($logs))
            <div class="col-md-6">

                <table class="table table-striped">
                    <th>
                        Log
                    </th>
                    <th>Create at</th>


                    @foreach($logs as $log)
                        <tr>
                            <td>{!! nl2br($log->content) !!}</td>
                            <td>{{$log->created_at}}</td>
                        </tr>
                    @endforeach

                </table>

                <div id="inventory-pagination-wrapper">{!! $logs->appends(Request::all())->render() !!}</div>
            </div>
                @endif
        </div>

    </div>
    {!! BsForm::close() !!}

@endsection
@section('scripts')

    <script>
        $(document).ready(function () {
            $('.colourSelect2').select2({
                placeholder: "Please Select",
            });
        });
    </script>
@endsection