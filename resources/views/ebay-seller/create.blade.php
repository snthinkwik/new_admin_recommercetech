@extends('app')
@section('title', 'Create New Category')
@section('content')


    <div class="container">

        <h2>Create New Ebay Seller</h2>

        @include('messages')

        {!! BsForm::open(['method' => 'post', 'route' => 'ebay-seller.save']) !!}
        <div class="row">
            <div class="col-md-6">
                <a href="{{route('ebay-seller.index')}}" class="btn btn-default "> Back</a>

                <div class="panel panel-default">
                    <div class="panel-heading"></div>
                    <div class="panel-body">
                        @if(isset($seller->id))
                            {!! BsForm::hidden('id',isset($seller->id)? $seller->id:null) !!}
                        @endif
                        {!! BsForm::groupText('name',isset($seller->name)?$seller->name:null) !!}
                            {!! BsForm::groupText('user_name',isset($seller->user_name)?$seller->user_name:null) !!}
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
