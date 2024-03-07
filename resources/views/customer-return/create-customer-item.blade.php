<?php
    use App\Models\Stock;
    use App\Models\Sale;
//$sold = \App\Stock::where('status', \App\Stock::STATUS_SOLD)->get();
ini_set("display_errors", "1");
ini_set('memory_limit', '1024M');


?>
@extends('app')

@section('title', 'Customer Return Items')

@section('content')
    <div class="container">
        <div class="p5">
            <a href="{{route('customer.return.index')}}" class="btn btn-default">Back</a>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered" border="1">
                <thead>
                <tr id="ebay-order-sort">
                    <th name="RCT">IMEI/Serial</th>
                    <th name="sale_id">Sale Id</th>

                    <th name="name">Name</th>
                    <th name="sale_price">Sale Price</th>
                    <th name="purchase_cost">Purchase Cost</th>
                    <th name="return_reason">Return Reason</th>
                    <th name="qb_invoice_id">QB Invoice ID</th>
                </tr>
                </thead>
                <tbody>
                @foreach($customerReturnItem as $return)

                    <?php
                    $stock = Stock::find($return->stock_id);
                    $sales = Sale::find($return->sale_id);
                    ?>
                    <tr>
                        <td>
                            @if(isset($return->stock_id))

                                @if(isset($stock))
                                    @if(!is_null($stock->imei))
                                        @if($stock->imei !== "")
                                            {{$stock->imei}}
                                        @else
                                            {{$stock->serial}}
                                        @endif
                                    @else


                                        {{$stock->serial}}
                                    @endif
                                @else
                                    -
                                @endif
                            @else
                                -
                            @endif
                        </td>

                        <td> {{$return->sale_id}}</td>


                        <td>{{$return->name}}</td>
                        <td>{{ money_format($return->sale_price)}}</td>
                        <td>{{ money_format($return->purchase_cost) }}</td>

                        <td>{{$return->return_reason}}</td>

                        <td>{{$return->qb_invoice_id}}</td>

                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>


    </div>

@endsection
