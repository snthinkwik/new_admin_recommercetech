@extends('email')

@section('content')

    <p>Hello,</p>
    <p>The following order is ready for dispatch.</p>
    <p>Number of items: {{ count($sale->stock) }}</p>
    <p>Please see attached the pick list.</p>

@endsection