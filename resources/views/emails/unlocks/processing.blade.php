@extends('email')

@section('content')

<p>Hi {{ $user->first_name }},</p>

<p>We are now processing the unlocks for your most recent order.</p>
  
<p>Here are the devices we are unlocking:</p>
  
<table border="0" cellpadding="0" cellspacing="0" width="100%" style="font-size:16px;color:#000000;">
    <thead>
    <tr>
        <th style="border: 1px solid #888; padding: 10px;">IMEI</th>
        <th style="border: 1px solid #888; padding: 10px;">Network</th>
        <th style="border: 1px solid #888; padding: 10px;">ETA</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($unlocks as $unlock)
        <?php
        $stockItem = $unlock->stock_item;
        ?>
        <tr>
            <td style="border: 1px solid #888; padding: 10px;">{{ $unlock->imei }}</td>
            <td style="border: 1px solid #888; padding: 10px;">{{ $unlock->network }}</td>
            <td style="border: 1px solid #888; padding: 10px;">
                {{ $unlock->eta->format('l F jS Y') }}</td>
        </tr>
    @endforeach
    </tbody>
</table>


<p>We will email you as soon as we receive confirmation that your devices are unlocked.</p>
<p>Please note that this is an automated process.</p>
@endsection

@section('regards')
    Regards,<br/>Recomm Support
@endsection