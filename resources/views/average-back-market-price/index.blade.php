@extends('app')

@section('title', 'Back Market Average Price')

@section('content')

    <div class="container-fluid">
        @include('messages')
        <a class="btn btn-success" href="{{route('average_price.master')}}" >Master</a>
        <a class="btn btn-success" href="{{route('average_price.ebay')}}" >eBay</a>
        <a class="btn btn-success" href="{{route('average_price.back_market')}}"> Back Market</a>

        <div class="row p10">
            @include('average-back-market-price.search-form')
        </div>

        <strong class="text-success">BuyBox Yes:{{$buyBoxYes}}</strong>
        <br>
        <strong class="text-danger">BuyBox No:{{$buyBoxNo}}</strong>

        <div id="ebay-order-items-wrapper">
            @include('average-back-market-price.list')
        </div>
        <div id="ebay-order-pagination-wrapper">{!! $backMarket->appends(Request::all())->render() !!}</div>


    </div>

@endsection

{{--@section('nav-right')--}}
{{--<div id="basket-wrapper" class="navbar-right pr0">--}}
{{--@include('basket.navbar')--}}
{{--</div>--}}
{{--@endsection--}}