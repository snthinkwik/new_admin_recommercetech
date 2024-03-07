
<div class="table small stock table-h-sticky">
    {{--<p class="small"><h5><b>Item Count:</b> {{$total}}</h5></p>--}}

    {{--{{dd(Request::all())}}--}}
    <div class="row" id="amountDisplay">
        <div class="col-sm-2">
            <p class="small"><h5><b>Total Quantity:</b> {{$totalQty}}</h5></p>
        </div>
        <div class="col-sm-3">
            <p class="small"><h5><b>Total EST Top 50 Stock Quantity:</b> {{$totalEstQty}}</h5></p>
        </div>



    </div>

    <div class="row" id="advanceAmountDisplay" style="display: none">
        <div class="col-sm-2">
            <p class="small"><h5><b>Total Quantity:</b> <span id="totalQty"></span></h5></p>
        </div>
        <div class="col-sm-3">
            <p class="small"><h5><b>Total EST Top 50 Stock Quantity:</b> <span id="totalEst"></span></h5></p>
        </div>



    </div>

    <table class="table table-bordered table-hover" id="filterTable"  style="display: none;">
        <thead>
        <tr id="ebay-order-sort" style="font-size: 10px;text-align: center !important; ">

            <th name="category" style="text-align: center">Category</th>
            <th name="make" style="text-align: center">Brand</th>
            <th name="product_name" style="text-align: center">Product Name</th>
            <th name="ean" style="text-align: center">EAN</th>
            <th name="model_no">Model_No</th>
            <th name="mpn" style="text-align: center">MPN</th>
            <th name="condition" style="text-align: center">Condition</th>
            <th style="text-align: center">Average Price</th>
            <th style="text-align: center">Recomm Offer Price VAT</th>
            <th style="text-align: center">Recomm Offer Price MRG</th>
            {{--<th style="text-align: center">NS Total sold past week</th>--}}
            <th name="best_price_from_named_seller" style="text-align: center">NAMED Seller Best Price £</th>
            <th name="best_price_network" style="text-align: center">Network </th>
            <th name="best_seller" style="text-align: center">Seller</th>
            <th name="best_seller_listing_rank" style="text-align: center">Listing rank</th>
            <th name="first_best_price" style="text-align: center">1st Best Price £</th>
            <th name="first_network" style="text-align: center">Network</th>
            <th name="first_seller" style="text-align: center">Seller</th>
            <th name="first_listing_rank" style="text-align: center">Listing rank </th>
            <th name="second_best_price" style="text-align: center">2st Best Price £</th>
            <th name="second_network" style="text-align: center">Network</th>
            <th name="second_seller" style="text-align: center">Seller</th>
            <th name="second_listing_rank" style="text-align: center">Listing rank</th>
            <th name="third_best_price" style="text-align: center">3st Best Price £</th>
            <th name="third_network" style="text-align: center">Network</th>
            <th name="third_seller" style="text-align: center">Seller</th>
            <th name="third_listing_rank" style="text-align: center">Listing rank</th>
            <th style="text-align: center">Est Top 50 Stock Qty</th>


            {{--<th>Average of 4 Top Seller Prices on eBay</th>--}}
        </tr>
        </thead>
        <tbody id="finalData">


        </tbody>
    </table>

    <table class="table table-bordered table-hover" id="secondTable" >
        <thead>
        <tr id="ebay-order-sort" style="font-size: 10px;text-align: center !important; ">

            <th name="category" style="text-align: center">Category</th>
            <th name="make" style="text-align: center">Brand</th>
            <th name="recomme_product_id" style="text-align: center">Recomm Product Id</th>
            <th name="product_name" style="text-align: center">Product Name</th>
            <th name="epid" style="text-align: center">eBay Product Id</th>

            <th name="ean" style="text-align: center">EAN</th>
            <th name="model_no">Model_No</th>
            <th name="mpn" style="text-align: center">MPN</th>
            <th name="condition" style="text-align: center">Condition</th>
            <th style="text-align: center">Average Price</th>
            <th style="text-align: center">Recomm Offer Price VAT</th>
            <th style="text-align: center">Recomm Offer Price MRG</th>
            {{--<th style="text-align: center">NS Total sold past week</th>--}}
            <th name="best_price_from_named_seller" style="text-align: center">NAMED Seller Best Price £</th>
            <th name="best_price_network" style="text-align: center">Network </th>
            <th name="best_seller" style="text-align: center">Seller</th>
            <th name="best_seller_listing_rank" style="text-align: center">Listing rank</th>
            <th name="first_best_price" style="text-align: center">1st Best Price £</th>
            <th name="first_network" style="text-align: center">Network</th>
            <th name="first_seller" style="text-align: center">Seller</th>
            <th name="first_listing_rank" style="text-align: center">Listing rank </th>
            <th name="second_best_price" style="text-align: center">2st Best Price £</th>
            <th name="second_network" style="text-align: center">Network</th>
            <th name="second_seller" style="text-align: center">Seller</th>
            <th name="second_listing_rank" style="text-align: center">Listing rank</th>
            <th name="third_best_price" style="text-align: center">3st Best Price £</th>
            <th name="third_network" style="text-align: center">Network</th>
            <th name="third_seller" style="text-align: center">Seller</th>
            <th name="third_listing_rank" style="text-align: center">Listing rank</th>
            <th style="text-align: center">Est Top 50 Stock Qty</th>
            <th style="text-align: center">Updated At</th>


            {{--<th>Average of 4 Top Seller Prices on eBay</th>--}}
        </tr>
        </thead>
        <tbody>
        @if(count($averagePrice))
        @foreach($averagePrice as $price)
        <tr style="font-size:10px; text-align: center">

            <?php

                $divided=0;
                if($price->best_price_from_named_seller){
                    $divided++;
                }
                if($price->first_best_price){
                    $divided++;
                }
            if($price->second_best_price){
                $divided++;
            }


            if($price->third_best_price){
                $divided++;
            }


            $best_price_from_named_seller=0;
            $first_best_price=floatval($price->first_best_price);
            $second_best_price=floatval($price->second_best_price);
            $third_best_price=floatval($price->third_best_price);
            if($price->best_price_from_named_seller!==''){
                $best_price_from_named_seller=$price->best_price_from_named_seller;
            }
            $average= ($best_price_from_named_seller+$first_best_price+$second_best_price+$third_best_price)/$divided;


            $secondNetworkList=[];
            if(strpos($price->second_network, ',') !== false){
                $secondNetworkList=explode(',',$price->second_network);
            }
            $firstNetworkList=[];
            if(strpos($price->first_network, ',') !== false){
                $firstNetworkList=explode(',',$price->first_network);
            }
            $thirdNetworkList=[];
            if(strpos($price->third_network, ',') !== false){
                $thirdNetworkList=explode(',',$price->third_network);
            }
            if(strpos($price->third_network, 'to') !== false){
                $thirdNetworkList=explode('to',$price->third_network);
            }
            if($average<20){
                if(isset($sellerFees)){
                    $shipping=$sellerFees->uk_shipping_cost_under_20;
                }

            }else{
                if(isset($sellerFees)){
                    $shipping=$sellerFees->uk_shipping_cost_above_20;
                }

            }

            $perStd=($average*$sellerFees->platform_fees)/100;
            $perMRG=($average*$sellerFees->platform_fees)/100;
            $vatStd=(($average/1.2)- ($perStd+$shipping+$sellerFees->accessories_cost_ex_vat))*0.80;

            $vatMRG=(($average)-($perMRG+$shipping+$sellerFees->accessories_cost_ex_vat))*0.76;





            ?>

                <td >{{$price->category}}</td>
                <td>{{$price->make}}</td>
                <td>{{$price->product_id}}</td>

                <td style="width: 10%; !important;" >{{$price->product_name}}</td>
                <td >

                    @if($price->epid!=="")
                    @if( !is_null($price->epid))
                        @foreach(json_decode($price->epid) as $pid)
                            {{$pid}}
                        @endforeach
                    @else
                        -
                    @endif
                    @else
                        -
                   @endif

                </td>
                <td> @if($price->ean!=="EAN"){{$price->ean}} @else - @endif</td>
                <td>{{$price->model_no}}</td>
                <td>{{$price->mpn}}</td>
                <td>{{$price->condition}}</td>
                <td>{{money_format($average) }}</td>
                <td>{{ money_format($vatStd)}}</td>
                <td>{{ money_format($vatMRG)}}</td>
                {{--<td width="4%">@if($price->different) <a href="{{route('average_price.ebay.single',['id'=>$price->id])}}">  {{$price->different}}</a> @endif</td>--}}
                <td style="background-color: #D3D3D3;"  >{{$price->best_price_from_named_seller!==""?  money_format($price->best_price_from_named_seller):'-' }}</td>
                <td style="background-color: #D3D3D3;" >{{$price->best_price_network}}</td>
                <td style="background-color: #D3D3D3;"  >{{$price->best_seller}}</td>
                <td style="background-color: #D3D3D3" >{{$price->best_seller_listing_rank}}</td>

                <td style="width: 4%" >{{$price->first_best_price?money_format($price->first_best_price):'' }}</td>

                @if(count($firstNetworkList))
                    <td >@foreach($firstNetworkList as $firstList) {{$firstList}}, @endforeach</td>

                @else
                    <td>{{$price->first_network}}</td>

                @endif


                <td>{{$price->first_seller}}</td>
                <td>{{$price->first_listing_rank}}</td>
                <td style="background-color: #D3D3D3;" >{{$price->second_best_price? money_format($price->second_best_price):'-' }}</td>



                @if(count($secondNetworkList))
                    <td  style="background-color: #D3D3D3;"  >@foreach($secondNetworkList as $list)  {{$list}}, @endforeach</td>
                @else
                    <td  style="background-color: #D3D3D3;"  >{{$price->second_network}}</td>
                    @endif

                <td  style="background-color: #D3D3D3;" >{{$price->second_seller}}</td>
                <td  style="background-color: #D3D3D3;" >{{$price->second_listing_rank}}</td>
                <td >{{$price->third_best_price? money_format($price->third_best_price):'-'}}</td>

                @if(count($thirdNetworkList))
                    <td>@foreach($thirdNetworkList as $thirdList) {{$thirdList}}, @endforeach</td>

                @else
                    <td>{{$price->third_network}}</td>

                @endif

                <td>{{$price->third_seller}}</td>
                <td>{{$price->third_listing_rank}}</td>
                <td>{{$price->est_top_50_stock_qty}}</td>
            <td style="width: 10%;">{{$price->updated_at}}</td>

        </tr>
           @endforeach
            @else

            <tr>
                <td colspan="26" align="center">
                    <strong class="text-dark">Product not found in the Recomm records. <a href="#"
                                                                                          data-toggle="modal"
                                                                                          data-target="#exampleModal"
                                                                                          class="text-success">
                            Click here </a> to search our partner databases</strong>

                </td>
            </tr>

            @endif

        </tbody>
    </table>


</div>


