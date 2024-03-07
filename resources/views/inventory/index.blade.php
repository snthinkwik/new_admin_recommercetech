@extends('app')

@section('title', 'Inventory')

@section('content')

    <div class="container">

        @include('messages')

        @include('inventory.nav')

        {{--@include('inventory.search')--}}
        <div id="inventory-items-wrapper">
            @include('inventory.item')
        </div>

        <div id="inventory-pagination-wrapper">{!! $inventory->appends(Request::all())->render() !!}</div>
    </div>
@endsection

@section('scripts')
    <script>


        $(document).ready(function () {

            $("#inventory-items-wrapper").on("click", "#checkAll", function (e) {
                e.stopPropagation();
                $('input:checkbox').not(this).prop('checked', this.checked);
            });
        });
    </script>
@endsection

{{--@section('nav-right')--}}
    {{--@if (Request::route()->getName() === 'stock' && Auth::user() && Auth::user()->type !== 'user' && in_array(Auth::user()->admin_type, ['admin', 'manager']))--}}
        {{--<form class="navbar-form navbar-right">--}}
            {{--<div class="btn-group navbar-right">--}}
                {{--<a class="btn btn-default" id="receive-stock-bulk-no-check" href="javascript:">Receive</a>--}}
            {{--</div>--}}
        {{--</form>--}}
    {{--@endif--}}

    {{--<div id="basket-wrapper" class="navbar-right pr0">--}}
        {{--@include('basket.navbar')--}}
    {{--</div>--}}
{{--@endsection--}}


