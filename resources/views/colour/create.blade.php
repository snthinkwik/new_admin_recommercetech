@extends('app')
@section('title', 'Add New Colour')
@section('content')


    <div class="container">

        <h2>Add New Colour</h2>
        <a href="{{route('colour.index')}}" class="btn btn-default">Back</a>

        @include('messages')

        {!! BsForm::open(['method' => 'post', 'route' => 'colour.save']) !!}
        <div class="row">
            <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="panel-heading"></div>
                    <div class="panel-body">
                        @if(isset($colour->id))
                            {!! BsForm::hidden('id',isset($colour->id)? $colour->id:null) !!}
                        @endif
                        {!! BsForm::groupText('pr_colour',isset($colour->pr_colour)?$colour->pr_colour:null) !!}
                    </div>

                    <div class="panel-body">

                        {!! BsForm::groupText('code',isset($colour->code)?$colour->code:null) !!}
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
