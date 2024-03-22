{{--@extends('layouts.app')--}}

{{--@section('content')--}}
{{--<div class="container">--}}
{{--    <div class="row justify-content-center">--}}
{{--        <div class="col-md-8">--}}
{{--            <div class="card">--}}
{{--                <div class="card-header">{{ __('Reset Password') }}</div>--}}

{{--                <div class="card-body">--}}
{{--                    @if (session('status'))--}}
{{--                        <div class="alert alert-success" role="alert">--}}
{{--                            {{ session('status') }}--}}
{{--                        </div>--}}
{{--                    @endif--}}

{{--                    <form method="POST" action="{{ route('password.email') }}">--}}
{{--                        @csrf--}}

{{--                        <div class="row mb-3">--}}
{{--                            <label for="email" class="col-md-4 col-form-label text-md-end">{{ __('Email Address') }}</label>--}}

{{--                            <div class="col-md-6">--}}
{{--                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>--}}

{{--                                @error('email')--}}
{{--                                    <span class="invalid-feedback" role="alert">--}}
{{--                                        <strong>{{ $message }}</strong>--}}
{{--                                    </span>--}}
{{--                                @enderror--}}
{{--                            </div>--}}
{{--                        </div>--}}

{{--                        <div class="row mb-0">--}}
{{--                            <div class="col-md-6 offset-md-4">--}}
{{--                                <button type="submit" class="btn btn-primary">--}}
{{--                                    {{ __('Send Password Reset Link') }}--}}
{{--                                </button>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </form>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--        </div>--}}
{{--    </div>--}}
{{--</div>--}}
{{--@endsection--}}



    <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>Password reset</title>

    <link rel='stylesheet' href='https://fonts.googleapis.com/css?family=Rubik:300,300i,400,400i,500,500i,700,700i,900,900i' type='text/css'>
    <!-- Stylesheet
    ========================= -->
    <link rel="stylesheet" type="text/css" href="{{asset('vendor/bootstrap/css/bootstrap.min.css')}}" />
    <link rel="stylesheet" type="text/css" href="{{asset('vendor/font-awesome/css/all.min.css')}}" />
    <link rel="stylesheet" type="text/css" href="{{asset('css/stylesheet.css')}}" />
</head>
<body>

<!-- Preloader -->
<div class="preloader">
    <div class="lds-ellipsis">
        <div></div>
        <div></div>
        <div></div>
        <div></div>
    </div>
</div>
<!-- Preloader End -->

<div id="main-wrapper" class="oxyy-login-register min-vh-100 d-flex flex-column">
    <div class="container my-auto">
        <div class="row">
            <div class="col-md-9 col-lg-7 col-xl-5 mx-auto my-4">
                <div class="bg-white border rounded p-4 py-sm-5 px-sm-5">
                    <div class="logo mb-4"> <a class="d-flex justify-content-center" href="{{ route('home') }}" title="Oxyy"><img src="{{asset('img/logo-lg.png')}}" alt="Oxyy"></a> </div>
                    <h3 class="text-center text-6 mt-2 mb-4">Forgot password?</h3>
                    <p class="text-muted text-center mb-4">Enter the email address or mobile number associated with your account.</p>
                    @if (session('status'))
                        <div class="alert alert-success">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if (count($errors) > 0)
                        <div class="alert alert-danger">
                            <strong>Whoops!</strong> There were some problems with your input.<br><br>
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <form id="forgotForm" class="form-horizontal" role="form" method="POST" action="{{ url('/password/email') }}">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <div class="form-group">
                            <input type="text" class="form-control" id="emailAddress" required placeholder="Enter Email or Mobile Number" name="email" value="{{ old('email') }}">
                        </div>
                        <div class="form-group">
                            <button class="btn btn-primary btn-block shadow-none mt-4 mb-3" type="submit">Continue</button>
                        </div>
                    </form>
                    <div class="d-flex align-items-center mb-3">
                        <hr class="flex-grow-1">
                        <span class="mx-2 text-muted">or</span>
                        <hr class="flex-grow-1">
                    </div>
                    <a class="btn btn-light shadow-none border btn-block" href="{{ route('home') }}" >Return to Log In</a>
                </div>
            </div>
        </div>
    </div>
    <div class="container-fluid bg-light py-2">
        <p class="text-center text-1 text-muted mb-0">Copyright © 2021 <a href="#">Recommerce Ltd</a>. All Rights Reserved.</p>
    </div>
</div>

<!-- Script -->
<script src="{{asset('vendor/jquery/jquery.min.js')}}"></script>
<script src="{{asset('vendor/bootstrap/js/bootstrap.bundle.min.js')}}"></script>
<script src="{{asset('js/theme.js')}}"></script>
</body>
</html>
