@extends('app')

@section('title', 'Quickbooks settings')

@section('content')
    <?php

        if(isset($setting)){
            $status=$setting->value;
            $id=$setting->id;
        }else{
            $status=0;
            $id=null;
        }
    ?>

    <div class="container">
        @include('admin.settings.nav')
        @include('messages')

        <h3>Dpd Shipping settings</h3>
        {!! BsForm::open(['method'=>'post','route' => 'admin.dpd-shipping.status']) !!}
        {!! Form::label('user-spend', 'Dpd Active?') !!}

            {!! BsForm::hidden('id', $id) !!}
            {!! BsForm::hidden('dpd_status', 0) !!}

            {!! BsForm::checkbox('dpd_status',1,$status, ['data-toggle' => 'toggle','class' => 'toggle-yes-no']) !!}

        {!! BsForm::groupSubmit('Update', ['class' => 'btn btn-primary']) !!}
        {!! Form::close() !!}
<hr>
        <small class="text-info"></small><br>
        AccessToken: <textarea class="form-control">@if(isset($dpdToken)){{$dpdToken->value}}@endif</textarea>
        <br/>
        <a href="{{route('admin.dpd-shipping.refresh-token')}}" class="btn btn-default">Refresh Token</a>

    </div>

@endsection

