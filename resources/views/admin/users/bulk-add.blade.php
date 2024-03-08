<?php

use App\Models\Country;

$countries = ['' => ''] + Country::orderBy('name')->pluck('name', 'name')->toArray();
//dd(Session::get('message')['error']);
$required = Session::get('Required');
?>
@extends('app')

@section('title', "Bulk add unregistered users")

@section('content')

<div class="container">

    <a class="btn btn-default" href="{{ route('admin.users.unregistered') }}"><i class="fa fa-reply"></i> Back to
        Unregistered Users</a>


    @if(isset(Session::get('message')[0]))
    <div class="alert alert-success">
        @foreach(Session::get('message')[0] as $value)
        {{$value}}
        <br>
        @endforeach
    </div>
    @endif
    @if(isset(Session::get('message')['error']))
    <div class="alert alert-danger">
        @foreach(Session::get('message')['error'] as $value)
        {{$value}}
        <br>
        @endforeach
    </div>

    @endif
    @if(isset($required))
    <div class="alert alert-danger">
        {{$required}}
    </div>
    @endif


    <h1>Bulk add unregistered users</h1>
    {!! BsForm::model($request->all(), ['route' => 'admin.users.bulk-add']) !!}
    {!!
    BsForm::groupTextArea(
    'emails_raw',
    null,
    ['placeholder' => "Separated by new lines, spaces or commas..."],
    ['label' => 'Emails', 'errors_name' => 'emails', 'errors_all' => true]
    )
    !!}
    {!! BsForm::groupSelect('country', $countries,'United Kingdom',["class"=>"network-select2 form-control"]) !!}
    {!! BsForm::groupSubmit('Save unregistered users') !!}
    {!! BsForm::close() !!}
</div>

@endsection
