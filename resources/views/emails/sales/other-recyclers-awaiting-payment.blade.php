@extends('email')

@section('content')

    <p>Hi >Chris</p>
    <p>The following other recyclers are over 7 days old and they still have not been paid:</p>
    <p>
        <small>
            [Recycler Order ID - Recycler Name]<br />
            [Item Name, Amount Expected]
        </small>
    </p>
    @foreach($data as $order)
        <p>
            <b>#{{ $order->recycler_order_number }} - {{ $order->recycler_name }}</b>
            @foreach($order->items as $item)
                <br />{{ $item->item_name }},
                {{ money_format($item->amount_expected) }}
            @endforeach
        </p>
    @endforeach
@endsection

@section('regards')
    Regards,<br/><strong>Recomm</strong>
@endsection
