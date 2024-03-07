@extends('app')

@section('title', 'Category')

@section('content')

    <div class="container">

        <h2>Category</h2>

        @include('messages')


        <a class="btn btn-default" href="{{route('category.create')}}"><i class="fa fa-plus"></i> Create Category</a>
        <a class="btn btn-info" href="{{route('cron-job.assigned')}}"> Run Cron Job eBayCategoryId Assigned To Category</a>

        <div id="universal-table-wrapper">
            @include('category.list')
        </div>

        <div id="universal-pagination-wrapper">
            {!! $category->appends(Request::all())->render() !!}
        </div>

    </div>

@endsection
