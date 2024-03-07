@extends('app')

@section('title', 'Colour')

@section('content')

    <div class="container">

        <h2>Colours</h2>

        @include('messages')


        <a class="btn btn-default" href="{{route('colour.create')}}"><i class="fa fa-plus"></i>Add Colour</a>


        <div id="universal-table-wrapper">
            @include('colour.list')
        </div>

        <div id="universal-pagination-wrapper">
            {!! $colour->appends(Request::all())->render() !!}
        </div>

    </div>

@endsection
