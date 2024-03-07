
@extends('app')

@section('title', 'Customer Return')

@section('content')



    <div class="container-fluid">
        <div class="row">
            <div class="col-xs-10">  <a class="btn btn-success col-8" href="{{route('customer.return.create')}}">Create Customer Return</a></div>
            <div class="col-xs-2">  <a class="btn btn-primary col-8" href="{{route('customer.export')}}">Export CSV</a></div>
        </div>

        @include('messages')
        @include('customer-return.search-form')
        <div id="ebay-order-items-wrapper">
       @include('customer-return.create-customer-list')
        </div>

    </div>
    {{--@endif--}}





@endsection