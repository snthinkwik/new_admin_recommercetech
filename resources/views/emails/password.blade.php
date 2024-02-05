@extends('email')

@section('content')
    <p style="margin:0">Click here to reset your password: <a href="{{ url('password/reset/'.$token) }}">{{ url('password/reset/'.$token) }}</a></p>     
@endsection