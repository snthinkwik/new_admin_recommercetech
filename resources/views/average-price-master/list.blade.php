<?php
    use App\Models\Product;
?>

<span class="text-success"><b>Total Validated:</b>{{$validate}}</span><br>
<span class="text-danger"><b>Total Unvalidated:</b>{{$unvalidated}}</span>
<div class="table small stock table-h-sticky">
    <table class="table table-bordered table-hover" id="secondTable">
        <thead>
        <tr id="ebay-order-sort" style="font-size: 10px;text-align: center !important; ">

            <th name="category" style="text-align: center">Category</th>
            <th name="brand" style="text-align: center">Brand</th>
            <th name="recomm_product_id" style="text-align: center">Recomm Product Id</th>
            <th name="ma_product_id" style="text-align: center">MA Product ID</th>
            <th name="product_name" style="text-align: center">Product Name</th>
            <th name="model_no" style="text-align: center">Model No</th>
            <th name="ean" style="text-align: center">EAN</th>
            {{--<th name="model_no">Model_No</th>--}}
            <th name="mpn" style="text-align: center">MPN</th>
            <th name="condition" style="text-align: center">Condition</th>
{{--            <th name="recomm_offer_price_vat">Recomm Offer Price VAT</th>--}}
{{--            <th name="recomm_offer_price_vat">Recomm Offer Price MRG</th>--}}
            <th style="text-align: center">UK Average Price £</th>
            <th style="text-align: center">UK Average Price (ex Vat) £</th>
            <th style="text-align: center">Ebay Average Price £</th>
            <th style="text-align: center">BM Average Price £</th>
            <th name="best_price_from_named_seller" style="text-align: center">Price Diff £</th>
            <th name="best_price_network" style="text-align: center">Diff %  </th>
            <th style="text-align: center">Buy (MRG)</th>
            <th style="text-align: center">Buy (VAT) </th>
            <th style="text-align: center">MA Store Update</th>
            <th style="text-align: center">Manual Price</th>
{{--            <th></th>--}}

        </tr>
        </thead>
        <tbody>


        @foreach($averagePrice as $price)
            <?php
                $product=Product::find($price->product_id);
            ?>
            <tr>

                <td align="center" @if($price->validate==="No") class="alert alert-danger" @endif>{{$price->category}}</td>
                <td align="center" @if($price->validate==="No") class="alert alert-danger" @endif>{{$price->make}}</td>
                <td align="center" @if($price->validate==="No") class="alert alert-danger" @endif>
                    <a href="{{route('products.single',['id'=>$price->product_id])}}">  {{$price->product_id}}</a>
                </td>

                <td align="center" @if($price->validate==="No") class="alert alert-danger" @endif>{{$price->ma_product_id}}</td>
                <td align="center" @if($price->validate==="No") class="alert alert-danger" @endif>{{$price->product_name}}</td>
                <td align="center" @if($price->validate==="No") class="alert alert-danger" @endif> {{$price->model}}</td>
                <td align="center" @if($price->validate==="No") class="alert alert-danger" @endif>{{$price->ean}}</td>
                {{--<td>{{$price->model}}</td>--}}
                <td align="center" @if($price->validate==="No") class="alert alert-danger" @endif>

                    @if(strpos($price->mpn, ',') !== false)
                        <?php
                        $mpn=explode(',',$price->mpn);
                        ?>

                            @if(strlen(implode(',',  $mpn)) > 20)

                                {{substr(implode(',', $mpn),0,20)}}
                                <span class="read-more-show hide_content">More<i class="fa fa-angle-down"></i></span>
                                <span class="read-more-content">
                                    {{substr(implode(',', $mpn),20,strlen(implode(',', $mpn)))}}
										<span class="read-more-hide hide_content">Less <i class="fa fa-angle-up"></i></span> </span>
                            @else
                                {{implode(',', $mpn)}}

                            @endif

                    @else
                        {{$price->mpn}}
                    @endif

                    </td>
                <td align="center" @if($price->validate==="No") class="alert alert-danger" @endif>{{ getCondition($price->condition)}}</td>
{{--                <td @if($price->validate==="No) class="alert alert-danger" @endif>Recomm Offer Price VAT </td>--}}
{{--                <td @if($price->validate==="No) class="alert alert-danger" @endif>Recomm Offer Price MRG</td>--}}
                <td align="center" @if($price->validate==="No") class="alert alert-danger" @endif>{{money_format($price->master_average_price)}}</td>
                <td align="center" @if($price->validate==="No") class="alert alert-danger" @endif>{{money_format($price->master_average_price/1.2)}}</td>
                <td align="center" @if($price->validate==="No") class="alert alert-danger" @endif>{{money_format($price->ebay_average_price)}}</td>
                <td align="center" @if($price->validate==="No") class="alert alert-danger" @endif>{{money_format($price->bm_average_price)}}</td>
                <td align="center" @if($price->validate==="No") class="alert alert-danger" @endif>{{money_format($price->price_diff) }}</td>

                <td align="center" @if($price->validate==="No") class="alert alert-danger" @endif>
                    {{number_format($price->diff_percentage,2).'%'}}
                </td>
                <?php
                    $mtPrice=($price->master_average_price-20)-($price->master_average_price*15/100);
                    ?>
                <td align="center" @if($price->validate==="No") class="alert alert-danger" @endif>{{ money_format($mtPrice) }}</td>
                <?php
                 $stPrice=(($price->master_average_price/1.2)-20)-(($price->master_average_price/1.2)*13/100);

                ?>
                <td align="center" @if($price->validate==="No") class="alert alert-danger" @endif>{{ money_format($stPrice)}} </td>
                @if(!is_null($price->type))
                <td align="center" style="width: 10%;">{{$price->type}}<br>
                    {{$price->ma_update_time}}
                </td>
                    @else
                    <td align="center">-</td>
                @endif
                <td colspan="2" align="center" style="width: 10%;">

                    <form method="post" action="{{route('average_price.master.edit')}}">
                    <div class="d-flex flex-row">

                        <div class="p-2">
                            <input type="text" class="form-control" name="manual_price" value="{{$price->manual_price >0 ? $price->manual_price:''}}">
                        </div>
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <input type="hidden" value="{{$price->id}}" name="id">
                        <div class="p-2" style="margin-left:10px">
                        <input type="submit" value="Validated" class="btn btn-success">
                        </div>

                    </div>
                    </form>
                </td>
            </tr>

        @endforeach
        </tbody>
    </table>



</div>
