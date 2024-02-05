@extends('email')

@section('content')

<p>Hi Dan,</p>
<p style="margin:0">The following customer has just registered on the Recomm system.</p>

<p>
    <strong>Company Name:</strong> {{ $user->company_name }}<br/>
                                        
    <strong>Address:</strong>
        @if ($user->address->line1) &nbsp;{{ $user->address->line1 }}<br /> @endif
        @if ($user->address->line2) &nbsp;{{ $user->address->line2 }} <br /> @endif
        @if ($user->address->city) &nbsp;{{ $user->address->city }} <br /> @endif
        @if ($user->address->country) &nbsp;{{ $user->address->country }} <br />@endif
        @if ($user->address->postcode) &nbsp;{{ $user->address->postcode }} <br />@endif
        <br/>                                    
    <strong>No. phones purchased a week:</strong> {{ $user->devices_per_week }}<br/>
    
    <strong>Description of business:</strong> {{ $user->business_description }}<br/>
</p>
                                        
<p>Please call {{ $user->full_name }} on {{ $user->phone }} to introduce them to RCT.</p>

@endsection

@section('regards')
    Regards,<br/><strong>Recomm Bot</strong>
@endsection