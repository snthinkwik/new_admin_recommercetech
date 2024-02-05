@extends('email')

@section('content')

    <p>Hi,</p>
              
    <p>Please submit the following unlocks to {{ $network }}</p>
              
@endsection

@section('regards')
<div>Regards,</div>
<div><b>Recomm</b></div>
@endsection