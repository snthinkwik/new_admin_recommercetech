@extends('email')

@section('content')

    <p>Hi {{ isset($user->id) ? $user->first_name : null }},</p>
    
    <p>
        <strong>Great news!</strong> Your iPhone is now permanently marked as
        unlocked in Apple's IMEI database - you can safely update to the latest iOS
        versions and remain unlocked. Here's how to complete the unlock process:
    </p>
    @if ($unlock->network === 'EE')
        <p>
            <strong>Please note that your phone will not be unlocked on the device until
                4pm tomorrow, this is EE's timeframe to update Apple.</strong>
        </p>
    @endif
    
    <p>Put incompatible sim into your iPhone</p>
    
    <p>Connect iPhone to either a WiFi or 3G network - the unlock should be delivered Over The Air and the iPhone should recognise your sim.</p>
    
    <p>
        <strong>If not:</strong> Connect iPhone to your computer and load iTunes -
        if it doesn't unlock then disconnect your iPhone, wait 5 seconds, then
        re-connect. You should now be unlocked and see the iTunes "Unlock Complete"
        screen (see below)
    </p>
        
    <p>                                
        <strong>If not:</strong> If iTunes still doesn't say you're unlocked simply restore your handset.
    </p>
    
    <p>
        <strong>Still problems?</strong><br/>
                                        
        If you see the 'SIM not valid' message - please ensure that you have given
        us the correct IMEI - you can check this on the Settings &gt;&gt; General&gt;&gt; About screen.
    </p>
        
    <p>                                    
        If you see 'No sim' message - the iPhone can't read your sim. Either the reader, or the sim card itself, is damaged.
    </p>
                                        
    <p>
        If the device appears unlocked but you get no service or no signal, or you
        see signal bars but cannot make calls, this means that your device is a
        lost/stolen unit and will only work OUTSIDE the country it was lost/stolen in.
    </p>
    
    <p>
        If you get the 'SIM not valid' message and HAVE given us the correct IMEI
        number, simply reply to this email and we will contact the carrier on your
        behalf and it will be automatically escalated to HIGHEST priority.
    </p>
                                        
@endsection

@section('regards')
    Regards,<br/>Recomm Support
@endsection