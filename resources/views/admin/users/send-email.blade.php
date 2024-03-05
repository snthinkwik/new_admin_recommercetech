<a class="btn btn-default btn-block" data-toggle="collapse" data-target="#send_email"> Send Email </a>
<div class="panel panel-default collapse" id="send_email">
    <div class="panel-body">
        <div class="p10">


            {!! BsForm::open(['method' => 'post', 'route' => 'admin.users.send-email']) !!}
            <div class="row p-10">


                {!! BsForm::hidden('id',$user->id) !!}

            </div>


            <div class="row p-10 mt-5">
                {!! BsForm::groupSubmit('Send Email', ['class' => 'btn-block btn btn-primary']) !!}
            </div>
            {!! BsForm::close() !!}

            <b>Check Email Format <a href="{{route('admin.email-format')}}"> Click Here</a></b>

        </div>



    </div>
</div>
