@extends('layouts.app')

@section('title', $user->full_name)

@section('content')
    <div class="container">

        @include('messages')
        <p><a href="{{ route('admin.users') }}" class="btn btn-default">Back to list</a></p>

        @if ($user->api_key)
            <h4>User's API key:</h4>
            <div class="well">{{ $user->api_key }}</div>
        @endif

        {!! BsForm::open(['route' => 'admin.users.api.generate-key']) !!}
        {!! BsForm::hidden('user_id', $user->id) !!}
        {!!
            BsForm::groupSubmit(
                $user->api_key ? 'Regenerate key' : 'Generate API key',
                $user->api_key
                    ? [
                            'class' => 'confirmed',
                            'data-confirm' => "Are you sure you want to regenerate the user's API key? They'll need to switch " .
                                "the key in their code as the old key will become invalid.",
                        ]
                    : null
            )
        !!}
        {!! BsForm::close() !!}
        <hr/>

        {!! BsForm::open(['route' => 'admin.users.login', 'method' => 'post', 'class' => 'ib']) !!}
        {!! Form::hidden('id', $user->id) !!}
        {!! Form::submit('Login', ['class' => 'btn btn-info login confirmed', 'data-confirm' => 'Are you sure you want to log in as this user?']) !!}
        {!! BsForm::close() !!}

        {!! Form::model($user, ['route' => 'admin.users.marketing-emails', 'id' => 'account-settings-form']) !!}
        <div class="checkbox">
            <label>
                {!! BsForm::hidden('id', $user->id) !!}
                {!! BsForm::hidden('marketing_emails_subscribe', 0) !!}
                {!! BsForm::checkbox('marketing_emails_subscribe', 1, null, ['data-toggle' => 'toggle']) !!}
                Marketing Emails
            </label>
        </div>
        <div class="response"></div>
        {!! Form::close() !!}

        <hr/>
        Account Suspended: {{ $user->suspended ? "Yes" : "No" }}
        {!! Form::open(['method' => 'post', 'route' => 'admin.users.suspend-user']) !!}
        {!! BsForm::hidden('id', $user->id) !!}
        {!! BsForm::hidden('suspended', 0) !!}
        {!! BsForm::checkbox('suspended', 1, $user->suspended, ['data-toggle' => 'toggle']) !!}
        {!! BsForm::submit('Save', ['class' => 'confirmed', 'data-confirm' => 'Are you sure you want to suspend/unsuspend this user?']) !!}
        {!! Form::close() !!}
        <hr/>

        <div class="row">
            <div class="col-md-4">
                {{--@if(!$user->invoice_api_id)--}}
                {!! BsForm::open(['method' => 'post', 'route' => 'admin.users.create-quickbooks-customer']) !!}
                {!! BsForm::hidden('id', $user->id) !!}
                {!! BsForm::groupSubmit('Create Quickbooks Customer',[
                        'class' => 'confirmed',
                        'data-confirm' => "Are you sure you want to regenerate the user's API key? They'll need to switch " .
                            "the key in their code as the old key will become invalid.",
                    ]) !!}


                {!! BsForm::close() !!}
                <span class="text-info small">If error message contains "entity [name] was referenced, but not declared. is not supported." it means that user address contains invalid characters.</span>
                {{--@endif--}}


                @include('admin.users.form')
            </div>
            <div class="col-md-8">
                {!! BsForm::open(['method' => 'post', 'route' => 'admin.users.remove-user']) !!}
                {!! BsForm::hidden('id', $user->id) !!}
                {!! BsForm::submit('Remove User',
                                        ['type' => 'submit',
                                        'class' => 'btn btn btn-danger btn-block confirmed',
                                        'data-toggle' => 'tooltip', 'title' => "Delete User", 'data-placement'=>'right',
                                        'data-confirm' => "Are you sure you want to delete this user?"])
                                    !!}
                {!! BsForm::close() !!}
                <a class="btn btn-default btn-block" href="{{ route('admin.users.single.emails', ['id' => $user->id]) }}">Emails <span class="badge">{{ $emails_count }}</span></a>
                @include('admin.users.sales')
                @include('admin.users.unlocks')
                @include('admin.users.returns')
                @include('admin.users.billing_address')

                @include('admin.users.address')
                @if(!is_null($user->document))
                    @include('admin.users.user-document')
                @endif

                @include('admin.users.send-email')

                <a class="btn btn-default btn-block flex-first" data-toggle="collapse" data-target="#sub-admin">
                    Sub Admin
                </a>
                <div class="panel panel-default collapse" id="sub-admin">
                    <div class="panel-body">
                        <div class="btn btn-success" data-toggle="modal" data-target="#addSubAdmin">
                            Add New Sub Admin
                        </div>
                        <!-- Modal -->
                        <div class="modal fade" id="addSubAdmin" tabindex="-1" aria-labelledby="admin" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="exampleModalLabel">Add Sub Admin</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        {!! BsForm::open(['method' => 'post','route' => 'sub-admin.add', 'class' => 'form-horizontal']) !!}

                                        <input type="hidden" name="master_id"  value="{{$user->id}}" >
                                        <div class="form-group @hasError('first_name')">
                                            <label class="col-md-4 control-label">First Name</label>
                                            <div class="col-md-6">
                                                <input type="text" class="form-control" name="first_name" required>
                                                @error('first_name') @enderror
                                            </div>
                                        </div>

                                        <div class="form-group @hasError('last_name')">
                                            <label class="col-md-4 control-label">Last Name</label>
                                            <div class="col-md-6">
                                                <input type="text" class="form-control" name="last_name" required>
                                                @error('last_name') @enderror
                                            </div>
                                        </div>

                                        <div class="form-group @hasError('email')">
                                            <label class="col-md-4 control-label">Email</label>
                                            <div class="col-md-6">
                                                <input type="email" class="form-control" name="email" required>
                                                @error('email') @enderror
                                            </div>
                                        </div>
                                        <div class="form-group @hasError('password')">
                                            <label class="col-md-4 control-label">Password</label>
                                            <div class="col-md-6">
                                                <input type="password" class="form-control" name="password" required>
                                                @error('password') @enderror
                                            </div>
                                        </div>
                                        <div class="modal-footer">


                                            {!! BsForm::submit('Save', ['class' => 'btn btn-primary btn-block']) !!}
                                        </div>
                                        {!! BsForm::close() !!}
                                    </div>

                                </div>
                            </div>
                        </div>
                        <div class="p10">




                            {!! BsForm::open(['method' => 'post', 'route' => 'admin.users.send-email']) !!}






                            {!! BsForm::close() !!}


                            <table class="table table-responsive">
                                <th>FirstName</th>
                                <th>LastName</th>
                                <th>Email</th>
                                <th>Delete</th>
                                @foreach($subAdmin as $admin)
                                    <tr>
                                        <td>{{$admin->first_name}}</td>
                                        <td>{{$admin->last_name}}</td>
                                        <td>{{$admin->email}}</td>

                                        <td><a href="{{route('sub-admin.remove',['id'=>$admin->id])}}"> <i class="fa fa-remove text-danger" aria-hidden="true"></i></a></td>
                                    </tr>
                                @endforeach
                            </table>

                            <div id="universal-pagination-wrapper">
                                {!! $subAdmin->appends(Request::all())->render() !!}
                            </div>


                        </div>
                    </div>
                </div>


                @if(!is_null($user->custom_payments))
                        @if(count($user->custom_payments))
                            <a class="btn btn-default btn-block" data-toggle="collapse" data-target="#custom-payments">Payments</a>
                            <div class="panel panel-default collapse" id="custom-payments">
                                <div class="panel-body">
                                    <table class="table table-bordered">
                                        <tr>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                        </tr>
                                        @foreach($user->custom_payments as $custom_payment)
                                            <tr>
                                                <td>{{ $custom_payment->amount_formatted }}</td>
                                                <td>{{ $custom_payment->status }}</td>
                                                <td>{{ $custom_payment->created_at->format('d/m/Y H:i:s') }}</td>
                                            </tr>
                                        @endforeach
                                    </table>
                                </div>
                            </div>
                        @endif
                  @endif
            </div>
        </div>

    </div>



@endsection
