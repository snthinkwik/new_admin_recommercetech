@extends('app')

@section('title', 'Lost Items')

@section('content')

<div class="container">
    <h2>Lost Items</h2>
    <div class="row">
        <div class="col-sm-12 col-lg-8">
            <div class="mb-4">
                <a class="btn btn-default mb5" href="{{ route('stock-take.view-lost-items-export') }}">Export</a>
            </div>
        </div>
        <div class="col-sm-12 col-lg-4">
            <div class="mb-4 text-bold text-right">
                <div class="row">
                    <div class="col-sm-5 col-lg-5">
                        Total Items: <span class="text-success mr-2">{{$itemCount}}</span>
                    </div>
                    <div class="col-sm-6 col-lg-6">

                        Total Value: <span class="text-danger mr-2">{{money_format($totalItemValue)}}</span>
                    </div>

                </div>
            </div>
        </div>
    </div>

    @include('stock-take.search-form')
    <div id="lost-items-wrapper">
        @include('stock-take.view-lost-items-list')
    </div>
    <div id="lost-item-pagination-wrapper">{!! $items->render() !!}</div>
</div>

@endsection
