@extends('email')

@section('content')

<p>Hi {{ $customer->first_name }},</p>
<p>I can see from you recent order you have purchased iPhones that are locked to @if($ee && $o2) EE and O2 @elseif($ee && !$o2) EE @elseif($o2 && !$ee) O2 @endif.</p>
<p>Do you want to unlock them?</p>
<p>
    @foreach($sale->stock as $stock)
        @if($stock->network == "EE" && strpos($stock->name, "iPhone") !== false)
            {{ $stock->name }} {{ $stock->capacity ? $stock->capacity_formatted : null }} - Locked to EE<br />
        @elseif($stock->network == "O2" && strpos($stock->name, "iPhone") !== false)
            {{ $stock->name }} {{ $stock->capacity ? $stock->capacity_formatted : null }} - Locked to O2<br />
        @endif
    @endforeach
</p>
<p>We've made the process simple, just click <a href="{{ route('unlocks.own-stock.new-order') }}">here</a> and we will unlock these for you.</p>

@endsection

@section('regards')
    Regards,<br/><strong>Recomm</strong>
@endsection