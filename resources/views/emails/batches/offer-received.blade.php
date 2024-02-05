@extends('email')

@section('content')
    <p>Hi {{ $user->first_name }},</p>   
    <p>We have now received your offer of {{ $batchOffer->offer_formatted }} for batch {{ $batchOffer->batch_id }}.</p>
    <p>We will send you another update shortly once all offers have been received.</p>
@endsection

@section('regards')
    Regards,<br/><strong>Recomm Sales</strong>
@endsection