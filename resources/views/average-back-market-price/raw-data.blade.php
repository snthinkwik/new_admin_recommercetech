@extends('app')

@section('title', 'Back Market Average Price')

@section('content')

    <div class="container-fluid">
        @include('messages')


                <strong class="text-success">Count:{{$count}}</strong>
        {{--        <br>--}}
        {{--        <strong class="text-danger">BuyBox No:{{$buyBoxNo}}</strong>--}}

        <div id="ebay-order-items-wrapper">

            <div class="table small stock table-h-sticky">
                <table class="table table-bordered table-hover ">
                    <thead>
                    <tr id="ebay-order-sort" style="font-size: 10px;text-align: center !important; ">
                        <th style="text-align: center">Back Market Product Id</th>
                        <th style="text-align: center">Sku</th>
                        <th style="text-align: center">Quantity</th>
                        <th style="text-align: center">Price</th>
                        <th style="text-align: center">Price For Buybox</th>

                        <th style="text-align: center">Condition</th>
                        <th style="text-align: center">Same Merchant Winner</th>
                        <th style="text-align: center">BuyBox</th>
                        <th style="text-align: center">EAN</th>



                    </tr>
                    </thead>
                    <tbody>
                    @foreach($rawData as $data)

                        <tr style="text-align: center">
                            <td>{{$data->product_id}}</td>
                            <td>{{$data->sku}}</td>
                            <td>{{$data->quantity}}</td>

                            <td>{{$data->price}}</td>
                            <td>{{$data->price_for_buybox}}</td>
                            <td>{{$data->condition}}</td>
                            <td>@if($data->same_merchant_winner) Yes @else No @endif</td>
                            <td>@if($data->buybox)Yes @else No @endif</td>
                            <td>
                               {{$data->ean}}
                            </td>
                        </tr>

                    @endforeach


                    </tbody>
                </table>
            </div>
        </div>
                <div id="ebay-order-pagination-wrapper">{!! $rawData->appends(Request::all())->render() !!}</div>


    </div>

@endsection

{{--@section('nav-right')--}}
{{--<div id="basket-wrapper" class="navbar-right pr0">--}}
{{--@include('basket.navbar')--}}
{{--</div>--}}
{{--@endsection--}}