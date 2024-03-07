@extends('app')

@section('title', "My Settings")
@include('scripts', ['required' => 'ckeditor'])
@section('scripts')
    <script>
        CKEDITOR.replaceAll('ckeditor-textarea');
    </script>
@endsection
@section('content')


    <?php


            $content='';
            if(!is_null($emailFormat)){
                $link="<a href=".env('TRG_UK_URL')."/multiple-upload-file/{userId}".">Go to Document Upload</a>";
                $content= str_replace($link,"%%Link%%",$emailFormat->message);
            }

    ?>

    <div class="container">
        @include('messages')

        {!! BsForm::open(['method' => 'post', 'route' => 'admin.save.email-format']) !!}
<div class="row p-10">

    {!! Form::label('subject', "Subject") !!}
    {!! BsForm::text('subject',!is_null($emailFormat)?$emailFormat->subject:'',['required' => 'required']) !!}

</div>

<div class="row p-10">

    {!! Form::label('message', "Message") !!}
    {!! BsForm::textArea('message',$content ,['required' => 'required','class' => 'ckeditor-textarea']) !!}
    <br>
    <b class="text-danger text-5">Note:<small>For Document Upload Link Use This %%Link%% </small></b>
</div>
        <br>


        <div class="row p-10">

            {!! Form::label('Kind Regard', "kind_regard") !!}
            {!! BsForm::text('kind_regard',!is_null($emailFormat)?$emailFormat->regard:'' ,['required' => 'required']) !!}

        </div>



        <div class="row p-10 mt-5">
        {!! BsForm::groupSubmit('Save Format', ['class' => 'btn btn-success']) !!}
        </div>
        {!! BsForm::close() !!}
    </div>


@endsection