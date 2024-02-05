@extends('email')

@section('content')

    <p>Hi {{ $user->first_name }},</p>
                                      
    <p>Your Recomm account has been suspended.</p>
                                    
    <p><strong>Suspended by:</strong> {{ $suspended }}</p>
                                        
    <p>Please contact us for further information.</p>
    <p>At this time you cannot place any new orders.</p>

@endsection

@section('regards')
    Regards,<br/><strong>Recomm</strong>
@endsection