@extends('app')

@section('title', 'eBay Seller')

@section('content')

    <div class="container">

        <h2>eBay Seller</h2>

        @include('messages')


        <a class="btn btn-default" href="{{route('ebay-seller.create')}}"><i class="fa fa-plus"></i> Create Ebay Seller</a>
        {{--<a class="btn btn-info" href="{{route('cron-job.assigned')}}"> Run Cron Job eBayCategoryId Assigned To Category</a>--}}

        <div id="universal-table-wrapper">
            @include('ebay-seller.list')
        </div>

        <div id="universal-pagination-wrapper">
            {{--{!! $category->appends(Request::all())->render() !!}--}}
        </div>

    </div>

@endsection
