@extends('app')

@section('title', 'Parts')

@section('content')

<div class="container">

    @include('messages')

    @if(Auth::user()->type === 'admin')
    <div class="mb15">
        <a href="{{ route('parts.add') }}">Add New Part</a> |
        <!-- <a href="{{ route('parts.stock-levels') }}">Update Stock Levels</a> | -->
        <!-- <a href="{{ route('parts.update-costs') }}">Update Costs</a> | -->
        <a href="{{ route('parts.update-costs') }}">Update Stock and Costs</a> |
        <a href="{{ route('parts.summary') }}">Parts Summary</a>
    </div>
    @endif

    @include('parts.search-form')
    <div id="parts-items-wrapper">
        @include('parts.list')
    </div>
    <div id="parts-pagination-wrapper">{!! $parts->appends(Request::All())->render() !!}</div>
</div>
@endsection

@section('nav-right')
<div id="basket-wrapper" class="navbar-right pr0">
    @include('basket.navbar')
</div>
@endsection