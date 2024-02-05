@extends('email')

@section('content')

<p>Hi Sales</p>

<p>RCT Ref - {{ $stock->id }} has had it's iCloud removed and is now ready for sale.</p>
<p>
    {{ $stock->name }}<br />
    {{ $stock->colour }}<br />
    {{ $stock->capacity_formatted }}<br />
    {{ $stock->network }}<br />
    {{ $stock->imei }}
</p>

@endsection

@section('regards')
    Regards,<br/><strong>Recomm Bot</strong>
@endsection