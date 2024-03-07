@extends('app')

@section('title', 'Master Average Price')

@section('content')



    <div class="container-fluid">


        @include('messages')
        <a class="btn btn-success" href="{{route('average_price.master')}}" >Master</a>
        <a class="btn btn-success" href="{{route('average_price.ebay')}}" >eBay</a>
{{--        <a class="btn btn-success" href="{{route('average_price.back_market')}}"> Back Market</a>--}}



        <div class="row p10">
        @include('average-price-master.search-form')
        </div>

        <div class="alert alert-danger font-2 d-flex flex-row" style="display: none !important;" id="error">
            <div class="p-2" ><i class="fa fa-exclamation-circle fa-2x" aria-hidden="true"></i></div>
            <div class="p-2" id="error-message"> </div>
        </div>


        <div id="ebay-order-items-wrapper">

            @include('average-price-master.list')
        </div>

        <div id="ebay-order-pagination-wrapper">{!! $averagePrice->appends(Request::all())->render() !!}</div>


    </div>

@endsection
