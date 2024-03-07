<?php
$suppliers = \App\Supplier::all();
?>
@extends('app')

@section('title', 'Parts')

@section('content')

<div class="container">

    @include('messages')
    <p><a class="btn btn-default" href="{{ route('parts') }}">Back to parts</a></p>
    @if(isset($part->id))
    <h2>Edit Part</h2>
    <div class="form-group">
        {!! BsForm::open(['method' => 'post', 'route' => 'parts.delete']) !!}
        {!! BsForm::hidden('id',  $part->id) !!}
        {!! BsForm::submit('Delete Part', ['class' => 'confirmed btn-danger', 'data-confirm' => "Are you sure you want to delete this part?"]) !!}
        {!! BsForm::close() !!}
    </div>
    @else
    <h2>Add New Part</h2>
    @endif
    <div class="row">
        <div class="col-md-6">
            {!! BsForm::open(['route' => 'parts.save', 'files' => 'true']) !!}
            @if(isset($part->id))
            {!! BsForm::hidden('id',isset($part->id)? $part->id:null) !!}
            @endif
            {!! Form::label('name', 'Part Name') !!}
            {!! BsForm::text('name', (isset($part->name) ? $part->name : null), ['placeholder' => 'Part Name', 'required']) !!}
            {!! Form::label('sku', 'Part SKU') !!}
            {!! BsForm::text('sku', (isset($part->sku) ? $part->sku : null), ['placeholder' => 'Part SKU']) !!}
            {!! Form::label('colour', 'Part Colour') !!}
            {!! BsForm::text('colour', (isset($part->colour) ? $part->colour : null), ['placeholder' => 'Part Colour', 'required']) !!}
            {!! Form::label('type', 'Part Type') !!}
            {!! BsForm::text('type', (isset($part->type) ? $part->type : null), ['placeholder' => 'Part Type', 'required']) !!}
            {!! Form::label('cost', 'Part Cost') !!}
            <div class="input-group">
                <div class="input-group-addon">£</div>
                {!! Form::number('cost', (isset($part->cost) ? $part->cost : 0), ['class' => 'form-control', 'placeholder' => 'Cost', 'min' => 0, 'step' => 0.01]) !!}
            </div>
            {!! Form::label('part_supplier', 'Part Supplier') !!}
            <select name="supplier" class="supplierSelect2 form-control">
                <option value=""></option>
                @foreach($suppliers as $supplier)
                <option value="{{$supplier->id}}" @if(isset($part->suppliers->id)) @if($part->suppliers->id == $supplier->id) selected @endif @endif >{{$supplier->name}}</option>
                @endforeach
            </select>
            <button type="button" class="btn btn-link btn-sm" data-toggle="modal" data-target="#myModal"> Add new
                Supplier
            </button>


            {!! BsForm::submit('Save', ['class' => 'mt10 btn btn-primary btn-block']) !!}
            {!! BsForm::close() !!}
            <div class="modal fade" id="myModal" role="dialog">
                <div class="modal-dialog">

                    <!-- Modal content-->
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                            <h4 class="modal-title">Add Suppliers</h4>
                        </div>
                        <div class="modal-body">

                            {!! BsForm::open(['route' => 'suppliers.add']) !!}

                            <div class="form-group">
                                {!! BsForm::groupText('name', null, ['required' => 'required']) !!}
                            </div>
                            <div class="form-group">
                                {!! BsForm::groupText('address_1', null, ['required' => 'required']) !!}
                            </div>
                            <div class="form-group">
                                {!! BsForm::groupText('address_2') !!}
                            </div>
                            <div class="form-group">
                                {!! BsForm::groupText('town', null, ['required' => 'required']) !!}
                            </div>
                            <div class="form-group">
                                {!! BsForm::groupText('county', null, ['required' => 'required']) !!}
                            </div>
                            <div class="form-group">
                                {!! BsForm::groupText('postcode', null, ['required' => 'required']) !!}
                            </div>
                            <div class="form-group">
                                {!! BsForm::groupText('email_address', null, ['required' => 'required']) !!}
                            </div>
                            <div class="form-group">
                                {!! BsForm::groupText('contact_name', null, ['required' => 'required']) !!}
                            </div>
                            <div class="form-group">
                                {!! BsForm::groupText('returns_email_address', null, ['required' => 'required']) !!}
                            </div>
                            <div class="form-group">
                                {!! BsForm::submit('Add', ['class' => 'btn btn-primary']) !!}
                            </div>

                            {!! BsForm::close() !!}
                        </div>

                    </div>

                </div>
            </div>
        </div>

        @if(isset($part) && $part->exists)
        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-heading">Log</div>
                <div class="panel-body">
                    @if(!count($part->part_logs))
                    <div class="alert alert-info">Nothing Found</div>
                    @else
                    <table class="table table-bordered">
                        <tr>
                            <th>User</th>
                            <th>Content</th>
                            <th class="text-center"><i class="fa fa-calendar"></i></th>
                        </tr>
                        @foreach($part->part_logs()->orderBy('id', 'desc')->get() as $log)
                        <tr>
                            <td>@if($log->user) <a
                                    href="{{ route('admin.users.single', ['id' => $log->user_id]) }}">{{ $log->user->full_name }}</a> @endif
                            </td>
                            <td>{!! nl2br($log->content) !!}</td>
                            <td class="small">{{ $log->created_at->format("d/m/Y H:i:s") }}</td>
                        </tr>
                        @endforeach
                    </table>
                    @endif
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection


@section('scripts')
<script>
    $(document).ready(function () {
        $('.supplierSelect2').select2({
            placeholder: "Select Supplier",
        });
    });
</script>
@endsection