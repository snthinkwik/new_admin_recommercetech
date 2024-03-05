<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Email</title>
</head>
<body style="font-family: sans-serif">

@section('header-logo')
    <div style="margin-bottom: 15px;">
        <img src="{{ asset('img/logo.png') }}" alt="Recomm" width="180">
    </div>
@show

<div style="margin-bottom: 15px">
    @yield('content')
</div>

@section('regards')
    <div>Kind Regards,</div>
    <div><b>Recomm</b></div>
@show

{{--@section('brand')

	<div><b>M:</b> 07535 239003</div>
	<div><b>T:</b> 01494 442265</div>
	<div style="margin-bottom: 15px"><b>W:</b> www.trg-uk.net</div>

	<div style="margin-bottom: 15px; font-size: 11px">Company Registration No. 07834275 - Registered in England and Wales.</div>
@show--}}

@yield('footer-company')

@yield('customer-id')

{{--<div style="font-size: 10px">
	This email and any files transmitted with it are confidential and intended solely for the use of the individual or
	entity to whom they are addressed. If you are not the intended recipient, you should not copy it, re-transmit it, use
	it or disclose its contents, but should return it to the sender immediately and delete your copy from your system.
	Recommerce Ltd does not accept legal responsibility for the contents of this message. Any views or opinions
	presented are solely those of the author and do not necessarily represent those of Recommerce Ltd.
</div>--}}

</body>
</html>
