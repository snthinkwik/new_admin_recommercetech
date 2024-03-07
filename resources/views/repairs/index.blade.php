@extends('app')

@section('title', 'Repairs')

@section('content')

<div class="container">

    @include('messages')

    @include('repairs.search-form')
    <div id="universal-table-wrapper">
        @include('repairs.list')
    </div>
    <div id="universal-pagination-wrapper">{!! $repairs->appends(Request::All())->render() !!}</div>
</div>
@endsection

@section('nav-right')
<div id="basket-wrapper" class="navbar-right pr0">
    @include('basket.navbar')
</div>
@endsection