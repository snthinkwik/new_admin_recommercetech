@extends('email')

@section('content')
    <p>Hi {{ $user->first_name }},</p>
                                        
    <p>Thank you for your offer on batch {{ $batchOffer->batch_id }} -{{ $batchOffer->batch->name }}.</p>

    <p>We have received a higher offer of: {{ $highestOffer }}</p>

    <p>Your offer was: {{ $batchOffer->offer_formatted }}.</p>

    <p>Don't let it get away! <a href="{{ $batchOffer->batch->trg_uk_url }}?userhash={{ $user->hash }}">Click here</a> if you wish to increase your offer.</p>
@endsection

@section('regards')
    Regards,<br/><strong>Recomm Sales</strong>
@endsection