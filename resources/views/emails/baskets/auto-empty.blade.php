@extends('email')

@section('content')
    <p>Good Evening {{ $user->first_name }}</p>

    <p>We noticed you still have the following items in your basket:</p>

    <p>
        @foreach ($user->basket as $item)
            {{ $item->long_name }}<br/>
        @endforeach
    </p>

    <p>We have now emptied your basket as most of these items are now sold but please do let us know if you need any assistance in placing an order.</p>

@endsection

@section('regards')
    Regards,<br/>Recomm Support
@endsection