<?php
use App\Email;
?>
@extends('email')

@section('content')


    <p>{!! $body !!}</p>

@endsection

@section('regards')
    Kind Regards,<br>
    {{$regard}}
    <br><br>

@endsection
