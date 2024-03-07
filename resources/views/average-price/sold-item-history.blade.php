@extends('app')

@section('title', 'eBay Orders')

@section('content')

    <div class="container">
        <div class="p10">
        <a href="{{route('average_price.ebay')}}" class="btn btn-default "> Back</a>
        </div>
            <table class="table table-responsive">
            <th>Content</th>
            <th>Total</th>
            <th>Date</th>

            @foreach($soldDetails as $sold)
            <tr>
                <td>
                    <?php
                        $total=[];

                       // dd(json_decode($sold->user_name));
                        foreach (json_decode($sold->user_name) as $key=>$rt){

                            array_push($total,$key."-".array_sum($rt));
                        }



                      $useName=implode('<br>',$total);



                      ?>


                        {!! nl2br($useName) !!}

                </td>
                <td>{{$sold->sold_no}}</td>
                <td>{{$sold->created_at}}</td>
            </tr>
                @endforeach
        </table>
    </div>
@endsection