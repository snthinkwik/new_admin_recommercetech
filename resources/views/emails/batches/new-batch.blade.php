@extends('email')

@section('content')

<p>{{ $user->first_name ? 'Hi ' . $user->first_name : 'Good Morning' }},</p>
<p>We have a new batch of customer returns available for purchase.</p>
<p>Please see attached and reply back with your best offer <strong>by 3pm on Monday</strong></p>
<p>There are {{ count($batch->stock) }} devices in this batch and we are looking for offers in the region of {{ money_format(config('app.money_format'), $batch->wanted_price) }}</p>
<p>Grade descriptions can be seen below.</p>

<p><strong>Minor Fault:</strong></p>
<p>
    All power up with working touch ID, faults include but not
    limited to; cracked glass, faulty speakers, battery issues, yellow LCD screen burn,
    bad lcd, pink halo or button faults. No repair attempts have been made. Water sensor
    may be tripped but there will be no water damage on the board.
</p>
<p><strong>Major Fault:</strong></p>
<p>
    All power up with working touch ID, faults include but not
    limited to; wifi faults, not activating, IC chip faults, not restoring, boot loop
    and faulty sim reader.
</p>
<p><strong>No Signs of Life:</strong></p>
<p>The device does not power on. No repair attempts have been made by Recomm.</p>
                                
<p><strong>iCloud Locked:</strong></p>
<p>The device is iCloud locked and it may also be faulty. We recommend buying this grade for parts only.</p>

@endsection