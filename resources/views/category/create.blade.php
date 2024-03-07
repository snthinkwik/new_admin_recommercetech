@extends('app')
@section('title', 'Create New Category')
@section('content')


    <div class="container">

        <h2>Create New Category</h2>

        @include('messages')

        {!! BsForm::open(['method' => 'post', 'route' => 'category.save', 'files' => 'true']) !!}
        <div class="row">
            <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="panel-heading"></div>
                    <div class="panel-body">
                        @if(isset($category->id))
                        {!! BsForm::hidden('id',isset($category->id)? $category->id:null) !!}
                        @endif
                        {!! BsForm::groupText('name',isset($category->name)?$category->name:null) !!}
                    </div>
                </div>
                <div class="col-md-12">
                    {!! BsForm::groupSubmit('Save', ['class' => 'btn-block']) !!}
                </div>
            </div>


        </div>
        {!! BsForm::close() !!}

    </div>

@endsection
