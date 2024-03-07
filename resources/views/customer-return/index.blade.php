
@extends('app')

@section('title', 'Seller Fees')

@section('content')



    <div class="container">
        <a class="btn btn-success" href="{{route('sales.customer_return.create')}}">Create Customer Return</a>
        @include('messages')
        <table class="table table-bordered table-hover">
            <tr>

                <th>Items Credited</th>
                <th>Value Of Credited</th>
                <th class="text-center"><i class="fa fa-pencil"></i></th>
            </tr>
            @foreach($customerReturn as $return)
                <tr>
                    <td>{{ $return->items_credited }}</td>
                    <td>{{ $return->value_of_credited }}</td>

                    <td>
                        <a class="btn btn-sm btn-default" href="{{route("sales.customer_return.single",['id'=>$return->id])}}"><i class="fa fa-pencil"></i></a>
                    </td>
                </tr>
            @endforeach
        </table>
    </div>
    {{--@endif--}}

@endsection