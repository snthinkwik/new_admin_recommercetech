@extends('email')

@section('content')

    <p>Hi {{ $user->first_name }},</p>
    <p>We received your offer of {{ $offer->offer_formatted }} for batch {{ $offer->batch_id }}.</p>
    <p>The batch has now sold for {{ $soldPrice }}.</p>

    @if(count($batchesForSale))
        <p>We currently have the following batches still available:</p>
                        
        @foreach($batchesForSale as $batch)
            <p>
                <strong>Batch No.</strong> {{ $batch->id }} @if($batch->name) - {{ $batch->name }} @endif<br/>
                <strong>No. items:</strong> {{ $batch->stock()->count() }}<br/>
                <a href="{{ $batch->trg_uk_url }}">Click here to view the batch.</a><br/>
            </p>
        @endforeach
    @endif

    <p>If there's anything else you require please contact us on what's app or reply to this email where we will be happy to help.</p>

@endsection

@section('regards')
    Regards,<br/><strong>Recomm Sales</strong>
@endsection