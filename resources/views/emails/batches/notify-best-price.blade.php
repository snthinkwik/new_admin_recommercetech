@extends('email')

@section('content')
    <p>Hi {{ $user->first_name }},</p>
    <p>We have now received your offer of {{ $batchOffer->offer_formatted }} for batch {{ $batchOffer->batch_id }}.</p>
    <p>The current highest offer is {{ $bestPrice }} - would you like to increase your offer to secure the deal?</p>
@endsection

@section('regards')
    Regards,<br/><strong>Recomm Sales</strong>
@endsection