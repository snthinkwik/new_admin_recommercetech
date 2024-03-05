@extends('email')

@section('content')

<p>Hi,</p>

<p>We have now completed the eBay fee import.</p>

<p>Total Success: {{$success}}</p>
<p>Total Failed: {{$failed}}</p>

@endsection

@section('regards')
    Regards,<br/><strong>Recomm Bot</strong>
@endsection