@extends('email')

@section('content')

<p>Hi {{ $user->first_name }},</p>
   
<p>Thank you for fixing the issues with your account.</p>
<p>We have now removed the limitation and you can place orders again.</p>

@endsection

@section('regards')
    Regards,<br/><strong>Recomm</strong>
@endsection