
<div class="table small stock table-h-sticky">
    <table class="table table-bordered table-hover ">
        <thead>
        <tr id="ebay-order-sort" style="font-size: 10px;text-align: center !important; ">
            <th  style="text-align: center">Category</th>
            <th style="text-align: center">Brand</th>
            <th style="text-align: center">Recomm Product Id</th>
            <th  style="text-align: center">Product Name</th>
            <th  style="text-align: center">BM Product Id</th>

            <th  style="text-align: center">EAN</th>
            <th  style="text-align: center">Model No</th>
            <th  style="text-align: center">MPN</th>
            <th  style="text-align: center">Condition</th>
            <th>BuyBox</th>
            <th  style="text-align: center">BuyBox Price</th>
{{--            <th  style="text-align: center">Our Price</th>--}}
            <th  style="text-align: center">Max Price</th>
            <th  style="text-align: center">Min Price</th>

            <th  style="text-align: center">Update At</th>




        </tr>
        </thead>
        <tbody>
        @foreach($backMarket as $data)

<?php
//
//            $divided=0;
//
//
//
//
//
//
//
//            if($data->price_for_buybox<20){
//                if(isset($sellerFees)){
//                    $shipping=$sellerFees->uk_shipping_cost_under_20;
//                }
//
//            }else{
//                if(isset($sellerFees)){
//                    $shipping=$sellerFees->uk_shipping_cost_above_20;
//                }
//
//            }
//
//            $perStd=($data->winner_price*$sellerFees->platform_fees)/100;
//
//            $perMRG=($data->winner_price*$sellerFees->platform_fees)/100;
//            $vatStd=(($data->winner_price/1.2)- ($perStd+$shipping+$sellerFees->accessories_cost_ex_vat))*0.80;
//
//            $vatMRG=(($data->winner_price)-($perMRG+$shipping+$sellerFees->accessories_cost_ex_vat))*0.76;
//
//
//            ?>

          <tr style="text-align: center">
              <td>{{$data->category}}</td>
              <td>{{$data->make}}</td>
              <td>{{$data->product_id}}</td>

              <td>{{$data->product_name}}</td>
              <td>{{$data->back_market_product_id}}</td>
              <td>{{$data->ean}}</td>
              <td>{{$data->model}}</td>

              <td  width="10%">

                      <?php
                        if(strpos($data->mpn, ' ') !== false){
                            $eanEx = explode(' ', $data->mpn);
                        }elseif(strpos($data->mpn, ',') !== false){
                            $eanEx = explode(',', $data->mpn);
                        }

//                        else{
//                            $eanEx=$data->mpn;
//                        }
                      ?>



                          @if(strpos($data->mpn, ',') !== false)
                              <?php
                              $mpn=explode(',',$data->mpn);
                              ?>
                              @if(strlen(implode(',',$mpn)) > 20)

                                  {{substr(implode(',',$mpn),0,20)}}
                                      <span class="read-more-show hide_content"><b>More</b><i class="fa fa-angle-down"></i></span>
                                  <span class="read-more-content">
                                    {{substr(implode(',',$mpn),20,strlen(implode(',', $mpn)))}}
                                      <span class="read-more-hide hide_content"><b>Less</b> <i class="fa fa-angle-up"></i></span> </span>
                              @else
                                  {{implode(',', $mpn)}}

                              @endif

                          @else
                              {{$data->mpn}}
                          @endif



{{--                  @if(count($eanEx)>1)--}}
{{--                      @foreach($eanEx as $mpn)--}}
{{--                          {{$mpn}},<br>--}}
{{--                              @endforeach--}}
{{--                          @else--}}

{{--                              {{$data->mpn}}--}}

{{--                          @endif--}}
                  </td>

              <td >{{getBackMarketConditionAestheticGrade($data->condition)}}</td>
              <td>
                  @if($data->buy_box)
                      Yes
                  @else
                      No
                  @endif
              </td>
              <td >{{ money_format($data->price_for_buybox) }}</td>
{{--              <td width="10%">{{ money_format(config('app.money_format'),$data->price)  }}</td>--}}
              @if($data->maxPrice)
              <td >{{ money_format($data->maxPrice->max_price)  }}</td>
              @else
                  <td>-</td>
              @endif
              @if($data->maxPrice)
                  <td >{{ money_format($data->maxPrice->min_price)  }}</td>
              @else
                  <td>-</td>
              @endif
              <td width="10%">{{$data->updated_at  }}</td>
          </tr>

        @endforeach




        </tbody>
    </table>
</div>
