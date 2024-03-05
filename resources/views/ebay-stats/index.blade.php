@extends('app')

@section('title', 'eBay Orders Stats')

@section('content')

<div class="container">
    <div class="mb-4">
        <a class="btn btn-default" href="{{ session('admin.ebay-orders') ?: route('admin.ebay-orders') }}">Back to list</a>
    </div>
    <div id="ebay-stats" class="collapse  show-if-has-error mb15">
        <p><a href="#ebay-stats" data-toggle="collapse"><i class="fa fa-close"></i></a></p>

    </div>
    @include('ebay-stats.search-form')
    @include('messages')

    <div id="universal-table-wrapper">
        @include('ebay-stats.list')
    </div>
</div>
@endsection

@section('nav-right')
<div id="basket-wrapper" class="navbar-right pr0">
    @include('basket.navbar')
</div>
@endsection