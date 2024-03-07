<?php
use App\Models\Stock;
use App\Models\EbayOrderItems;
use App\Models\EbayOrders;
$ownerList = EbayOrderItems::getAvailableOwnerWithKeys();
$saleType = EbayOrderItems::getAvailableSaleType();
$statusList = EbayOrders::getAvailableStatusWithKeys();
$validVatType = [];
$stockAvailable = false;
$testStatusList = [];
$vatTypeFlag = [];
if ($eBayOrder->EbayOrderItems) {
    foreach ($eBayOrder->EbayOrderItems as $item) {


        if ($item->quantity > 1) {

            if (!is_null(json_decode($item->stock_id))) {
                foreach (json_decode($item->stock_id) as $stockId) {


                    $stockAvailable = true;
                    if (!is_null(getCheckValidVatType($eBayOrder->post_to_country, getStockDetatils($stockId)->vat_type, $item->tax_percentage))) {
                        array_push($validVatType, getCheckValidVatType($eBayOrder->post_to_country, getStockDetatils($stockId)->vat_type, $item->tax_percentage));
                    }
                }

            }

        } else {
            if(!is_null($item->stock)){
                $stockAvailable = true;
                if (!is_null(getCheckValidVatType($eBayOrder->post_to_country, $item->stock->vat_type, $item->tax_percentage))) {
                        array_push($validVatType, getCheckValidVatType($eBayOrder->post_to_country, $item->stock->vat_type, $item->tax_percentage));
                    }


            }

        }
    }
}



?>
@extends('app')

@section('title', 'eBay Order View')

@section('content')

    <div class="container single-stock-product">
        @include('messages')
        <div class="alert alert-success" role="alert" id="message_success" style="display: none">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
            eBay Order SuccessFully Allocated to Stock
        </div>
        <div class="row p10">
            <div class="col-lg-10"><a class="btn btn-default hide-print" href="{{ route('admin.ebay-orders') }}">Back to
                    list</a></div>
            <div class="col-lg-2">

                @if(count($validVatType)>0)
                    <form action="{{route('ebay.create.invoice')}}" method="post" id="verificationForm">

                        <div class="modal fade" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel"
                             aria-hidden="true"
                             id="mi-modal">
                            <div class="modal-dialog modal-sm">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <button type="button" class="close" data-dismiss="modal"
                                                aria-label="Close"><span
                                                aria-hidden="true">&times;</span></button>
                                        <h4 class="modal-title" id="myModalLabel">Manager Authorisation Code
                                            Required</h4>
                                    </div>
                                    <div class="modal-body">
                                        <div id="errorModel"></div>
                                        <label>Code</label>
                                        <input type="text" name="code" id="verification_code" class="form-control"
                                               required>
                                    </div>

                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-default" id="modal-btn-si">Verify</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" name="_token" value="{{{ csrf_token() }}}"/>


                        <input type="hidden" name="id" value="{{$eBayOrder->id}}">


                        <input type="submit" class="btn btn-primary" value="Create Sales"
                               @if(!$stockAvailable || !is_null($eBayOrder->Newsale) ) disabled @endif id="addSales">

                    </form>

                @else

                    <form action="{{route('ebay.create.invoice')}}" method="post" id="verificationForm2">

                        <div class="modal fade" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel"
                             aria-hidden="true"
                             id="mi-modal">
                            <div class="modal-dialog modal-sm">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <button type="button" class="close" data-dismiss="modal"
                                                aria-label="Close"><span
                                                aria-hidden="true">&times;</span></button>
                                        <h4 class="modal-title" id="myModalLabel">Manager Authorisation Code
                                            Required</h4>
                                    </div>
                                    <div class="modal-body">
                                        <div id="errorModel"></div>
                                        <label>Code</label>
                                        <input type="text" name="code" id="verification_code" class="form-control"
                                               value="784199" required>
                                    </div>

                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-default" id="modal-btn-si">Verify</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" name="_token" value="{{{ csrf_token() }}}"/>


                        <input type="hidden" name="id" value="{{$eBayOrder->id}}">


                        <input type="submit" class="btn btn-primary" value="Create Sales" id="addSelect2"
                               @if(!$stockAvailable || !is_null($eBayOrder->Newsale) ) disabled @endif >

                    </form>
                @endif

            </div>

        </div>


        @if(count($validVatType)>0)
            <div class="alert alert-danger" role="alert">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span>
                </button>
                Invalid VAT Type can allow the order to progress with manager approval only
            </div>
        @endif


        <div class="alert alert-danger" id="error_message" role="alert" style="display: none">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span
                    aria-hidden="true">&times;</span>
            </button>
            <span id="message"></span>

        </div>
        <div class="report-print">
            <div class="row mb-2 align-items-md-center flex">
                <div class="col-sm-6 col-md-6 col-lg-6">
                    <p>
                        <strong>Sales Record No.: </strong>{{$eBayOrder['sales_record_number']}}
                    </p>
                </div>
                {{--                <div class="col-sm-4 col-md-4 col-lg-4 @if($SoldFor > 0) text-success @else text-danger @endif text-md-center">--}}

                {{--                </div>--}}

                <div class="col-sm-6 col-md-6 col-lg-6">
                    <p class="text-lg-right">
                        <strong>Order number: </strong> {{$eBayOrder['order_id']}}
                    </p>
                    <input type="hidden" id="order_id" value="{{$eBayOrder['id']}}">
                </div>
            </div>
        </div>


        <div class="panel panel-default page-break">

            <div class="panel-heading"><span><strong>Item Sold:</strong></span>
                <span style="float: right"
                      class="text-info"><strong>Platform: {{$eBayOrder['platform']}}</strong></span>
            </div>
            <div class="panel-body">
                @foreach($eBayOrder->EbayOrderItems as $item)
                    <?php

                    $taxRate = 0;
                    $totalCosts = 0;
                    $purchasePrice = 0;
                    $vatType = '';
                    $totalPurchaseQty = 0;
                    $totalProfitQty = 0;
                    $totalTrueProfitQty = 0;
                    $profit = 0;
                    $trueProfit = 0;
                    $vatExValue = 0;
                    $MargVat = 0;

                    $totalSalePrice = 0;
                    $totalPurchasePrice = 0;
                    $totalProfit = 0;
                    $totalTrueProfit = 0;
                    $grossProfit = 0;
                    $grossProfitPercentage = 0;
                    $totalGrossProfit = 0;
                    $totalGrossProfitPercentage = 0;
                    $totalExVatPrice = 0;
                    $profitPercentage = 0;
                    $totalProfitPercentage = 0;
                    $totalTrueProfitPercentage = 0;
                    $trueProfitPercentage = 0;
                    $totalCostsFinal = 0;
                    $count = 0;


                    if ($item->quantity > 1) {

                        if (!is_null(json_decode($item->stock_id))) {
                            foreach (json_decode($item->stock_id) as $stockId) {

                                $totalCosts += getStockDetatils($stockId)->total_cost_with_repair;
                                $taxRate = $item->tax_percentage * 100 > 0 ? ($item->tax_percentage) : 0;
                                $purchasePrice += getStockDetatils($stockId)->purchase_price;
                                $vatType = getStockDetatils($stockId)->vat_type;

//                                if ($item->tax_percentage * 100 > 0 && $vatType === "Standard" || !$item->tax_percentage * 100 && $vatType === "Margin") {
//                                    $vatType = "Standard";
//                                } else {
//                                    $vatType = "Margin";
//                                }

                                if (!$item->tax_percentage) {
                                    if ($vatType === Stock::VAT_TYPE_MAG) {
                                        array_push($vatTypeFlag, true);
                                    } else {
                                        array_push($vatTypeFlag, false);
                                    }
                                } else {


                                    if ($vatType === Stock::VAT_TYPE_STD) {
                                        array_push($vatTypeFlag, true);
                                    } else {
                                        array_push($vatTypeFlag, false);
                                    }

                                }




                            }

                            if($eBayOrder->platform ===Stock::PLATFROM_MOBILE_ADVANTAGE || $eBayOrder->platform ===Stock::PLATFROM_EBAY ){
                                $stPrice = $item['individual_item_price'] * $item['quantity'];
                            }else{
                                $stPrice = $item['individual_item_price'];
                            }

                            $calculations = calculationOfProfitEbay($taxRate, $stPrice, $totalCosts, $vatType, $purchasePrice);
                            $profit = $calculations['profit'];
                            $trueProfit = $calculations['true_profit'];
                            $vatExValue = $calculations['total_price_ex_vat'];
                            $MargVat = $calculations['marg_vat'];
                        }


                    } else {
                        if (isset($item->stock)) {
                            $totalCosts = $item->stock->total_cost_with_repair;
                            $taxRate = $item->tax_percentage * 100 > 0 ? ($item->tax_percentage) : 0;
                            $purchasePrice = $item->stock->purchase_price;
                            $vatType = $item->stock->vat_type;

                            if (!$item->tax_percentage) {
                                if ($vatType === Stock::VAT_TYPE_MAG) {
                                    array_push($vatTypeFlag, true);
                                } else {
                                    array_push($vatTypeFlag, false);
                                }
                            } else {
                                if ($vatType === Stock::VAT_TYPE_STD) {
                                    array_push($vatTypeFlag, true);
                                } else {
                                    array_push($vatTypeFlag, false);
                                }

                            }

                        }
                        if ($item->tax_percentage * 100 > 0 || !$item->tax_percentage * 100 && $vatType === "Standard") {
                            $vatType = "Standard";
                        } else {
                            $vatType = "Margin";
                        }

                        $individualSalesPrice = $item['individual_item_price'] * $item['quantity'];
                        $calculations = calculationOfProfitEbay($taxRate, $individualSalesPrice, $totalCosts, $vatType, $purchasePrice);
                        $profit += $calculations['profit'];
                        $trueProfit += $calculations['true_profit'];
                        $vatExValue += $calculations['total_price_ex_vat'];
                        $MargVat += $calculations['marg_vat'];


                        $taxRate = 0;
                        $totalCosts = 0;
                        $purchasePrice = 0;
                        $vatType = '';
                        if ($item->quantity > 1 && !is_null(json_decode($item->stock_id))) {

                            foreach (json_decode($item->stock_id) as $stockId) {

                                $totalCostsFinal += getStockDetatils($stockId)->total_cost_with_repair;
                                $taxRate = $item->tax_percentage * 100 > 0 ? ($item->tax_percentage) : 0;
                                $purchasePrice += getStockDetatils($stockId)->purchase_price;
                                $vatType = getStockDetatils($stockId)->vat_type;
                                $totalPurchasePrice += count(json_decode($item->stock_id)) ? getStockDetatils($stockId)->purchase_price : $item->stock->purchase_price;

                                if ($item->tax_percentage * 100 > 0 || !$item->tax_percentage * 100 && $vatType === "Standard") {
                                    $vatType = "Standard";
                                } else {
                                    $vatType = "Margin";
                                }
                            }

                            if($eBayOrder->platform===Stock::PLATFROM_MOBILE_ADVANTAGE || $eBayOrder->platform === Stock::PLATFROM_EBAY )
                            {
                                $individualItemPrice = $item['individual_item_price'] * $item['quantity'];
                            }else
                            {
                                $individualItemPrice = $item['individual_item_price'];
                            }

                            $calculations = calculationOfProfitEbay($taxRate, $individualItemPrice, $totalCostsFinal, $vatType, $purchasePrice);
                            $count++;
                            $totalProfit = $calculations['profit'];
                            $totalTrueProfit = $calculations['true_profit'];
                            $totalExVatPrice = $calculations['total_price_ex_vat'];
                            $totalSalePrice = $individualItemPrice;
                        } else {
                            if (isset($item->stock)) {

                                $totalCostsFinal += $item->stock->total_cost_with_repair;
                                $taxRate = $item->tax_percentage * 100 > 0 ? ($item->tax_percentage) : 0;
                                $purchasePrice = $item->stock->purchase_price;
                                $vatType = $item->stock->vat_type;

                                if ($item->tax_percentage * 100 > 0 || !$item->tax_percentage * 100 && $vatType === "Standard") {
                                    $vatType = "Standard";
                                } else {
                                    $vatType = "Margin";
                                }

                                $individualItemST = $item['individual_item_price'] * $item['quantity'];
                                $calculations = calculationOfProfitEbay($taxRate, $individualItemST, $item->stock->total_cost_with_repair, $vatType, $purchasePrice);

                                $count++;

                                $totalPurchasePrice += !empty(json_decode($item->stock_id)) ? $purchasePrice : $item->stock->purchase_price;
                                $totalProfit += $calculations['profit'];
                                $totalTrueProfit += $calculations['true_profit'];
                                $totalExVatPrice += $calculations['total_price_ex_vat'];
                                $totalSalePrice += $individualItemST;
                            }

                        }

                        if ($item->tax_percentage * 100 > 0 || !$item->tax_percentage * 100 && $vatType === "Standard") {
                            $totalProfitPercentage = $totalExVatPrice ? number_format($totalProfit / $totalExVatPrice * 100, 2) : 0;
                            $totalTrueProfitPercentage = $totalExVatPrice ? number_format($totalTrueProfit / $totalExVatPrice * 100, 2) : 0;
                        } else {
                            $totalProfitPercentage = $totalSalePrice ? number_format($totalProfit / $totalSalePrice * 100, 2) : 0;
                            $totalTrueProfitPercentage = $totalSalePrice ? number_format($totalTrueProfit / $totalSalePrice * 100, 2) : 0;
                        }
                    }
                    ?>

                    <div class="row mb-2">
                        <div class="col-sm-12 col-md-3">
                            <img src="{{$item['item_image']}}" class="img-cover img-responsive mb-1">
                        </div>
                        <div class=" col-sm-12 col-md-9 ">
                            <div class="row mb-2 mt-5">
                                <div class="col-sm-12 text-500">
                                    Item Name:
                                    <a href="{{'https://ebay.co.uk/itm/'.$item['item_number']}}" target="_blank">
                                        {{$item['item_name']}}
                                    </a>
                                </div>
                            </div>
                            <div class="row mt-5">
                                <div class="col-sm-6 col-md-6 mb-2">
                                    <div class="row">
                                        <div class="col-xs-5 col-sm-4 text-500">
                                            Item number:
                                        </div>
                                        <div class="col-xs-7 col-sm-8 ">
                                            {{$item['item_number']}}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-md-6 mb-2">
                                    <div class="row">
                                        <div class="col-xs-5 col-sm-4 text-500">
                                            Custom label:
                                        </div>
                                        <div class="col-xs-7 col-sm-8 ">
                                            @if(!is_null($item->stock))
                                                <a href="{{route('stock.single',['id'=>$item->stock->id])}}"
                                                   target="_blank"> {{$item['item_sku']}}</a>
                                            @else
                                                {{$item['item_sku']}}
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6 col-md-6 mb-2">
                                    <div class="row">
                                        <div class="col-xs-5 col-sm-4 text-500">
                                            Quantity:
                                        </div>
                                        <div class="col-xs-7 col-sm-8 ">
                                            {{$item['quantity']}}
                                        </div>
                                    </div>
                                </div>

                                <div class="col-sm-6 col-md-6 mb-2">
                                    <div class="row">
                                        <div class="col-xs-5 col-sm-4 text-500">
                                            Total Item price:
                                        </div>
                                        <div class="col-xs-7 col-sm-8 ">
                                            @if($eBayOrder['currency_code']=="GBP")
                                                <i class="fa fa-gbp"></i>
                                            @elseif($eBayOrder['currency_code']=="EUR")
                                                <i class="fa fa-usd" aria-hidden="true"></i>
                                            @endif
                                            <?php
                                            $discount = !is_null($eBayOrder['total_discount']) || !$eBayOrder['total_discount'] ? $eBayOrder['total_discount'] : 0;
                                            $res = preg_replace('/-+/', '', $discount);
                                            ?>

                                            @if($eBayOrder['platform']===Stock::PLATFROM_MOBILE_ADVANTAGE || $eBayOrder->platform === Stock::PLATFROM_EBAY  )

                                                {{money_format($item['individual_item_price']*$item['quantity'] )}}

                                            @else
                                                {{money_format($item['individual_item_price'] )}}
                                            @endif


                                            @if($res>0)
                                                <i class="fa fa-info-circle" aria-hidden="true"
                                                   title="Total Price With Discount"></i>
                                            @endif

                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6 col-md-6 mb-2">
                                    <div class="row">
                                        {{--<div class="col-xs-5 col-sm-4 text-500">--}}
                                        {{--Discount price:--}}
                                        {{--</div>--}}
                                        {{--<div class="col-xs-7 col-sm-8 ">--}}
                                        {{--@if($eBayOrder['currency_code']=="GBP")--}}
                                        {{--<i class="fa fa-gbp"></i>--}}
                                        {{--@elseif($eBayOrder['currency_code']=="EUR")--}}
                                        {{--<i class="fa fa-usd" aria-hidden="true"></i>--}}
                                        {{--@endif--}}
                                        {{--{{number_format($item['individual_item_discount_price'],2)}}--}}
                                        {{--</div>--}}
                                    </div>
                                </div>


                                <div class="col-sm-6 col-md-6 mb-2">
                                    <div class="row">
                                        <div class="col-xs-5 col-md-6 col-lg-4 text-500">
                                            Tax Percentage:
                                        </div>


                                        <div class="col-xs-7 col-sm-8 ">

                                            @if(isset($item->tax_percentage))
                                                {{number_format($item->tax_percentage*100,2)."%"}}
                                                <a href="" id="edit_{{$item->id}}" class="edit"> <i class="fa fa-pencil"
                                                                                                    aria-hidden="true"></i></a>

                                            @endif<br>


                                            <div class="form-group" style="display: none;" id="rate_box_{{$item->id}}">
                                                <div class="input-group">
                                                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                                    <input type="hidden" name="eBay_order_id" value="{{$item->id}}"
                                                           id="id_{{$item->id}}">

                                                    <input type="text" name="rate_{{$item->id}}"
                                                           class="form-control value" id="{{"rate_".$item->id}}"
                                                           placeholder="0.20 or 0.00"
                                                    >

                                                    <span class="input-group-btn">

                                                    <button class="btn fg" id="{{"btn_".$item->id}}">
                                                        <i class="fa fa-check"></i>

                                                    </button>


									            </span>
                                                </div>


                                            </div>


                                        </div>

                                    </div>
                                </div>

                            </div>
                            <div class="row">
                                <div class="col-sm-6 col-md-6 mb-2">
                                    <div class="row">
                                        <div class="col-xs-5 col-sm-4 text-500">
                                            Stock Id:
                                        </div>
                                        <div class="col-xs-7 col-sm-8 ">


                                            @if($item->quantity>1 && !is_null(json_decode($item->stock_id)))

                                                @foreach(json_decode($item->stock_id) as $stockId)
                                                    <a href="{{route('stock.single',['id'=>$stockId])}}">{{$stockId}}</a>
                                                    ,

                                                @endforeach
                                            @else
                                                @if(isset($item->stock))
                                                    <a href="{{route('stock.single',['id'=>$item->stock->id])}}">{{$item->stock->id}}</a>
                                                @endif
                                            @endif


                                        </div>
                                    </div>
                                </div>


                                <div class="col-sm-6 col-md-6 mb-2">
                                    <div class="row">
                                        <div class="col-xs-5 col-sm-4 text-500">
                                            Sell Price Ex Vat:
                                        </div>
                                        <div class="col-xs-7 col-sm-8 ">
                                            @if(($item->tax_percentage*100)>0)
                                                @if($eBayOrder['platform']===Stock::PLATFROM_MOBILE_ADVANTAGE || $eBayOrder->platform === Stock::PLATFROM_EBAY  )
                                                    {{money_format(($item->individual_item_price * $item->quantity)/1.2)}}
                                                @else
                                                    {{money_format(($item->individual_item_price)/1.2)}}

                                                @endif
                                            @else
                                                N/A
                                            @endif
                                        </div>
                                    </div>

                                </div>
                                <div class="col-sm-6 col-md-6 mb-2">
                                    <div class="row">
                                        <div class="col-xs-5 col-sm-4 text-500">
                                            Product Name:
                                        </div>
                                        <div class="col-xs-7 col-sm-8 ">


                                            @if($item->quantity>1 && !is_null(json_decode($item->stock_id)))

                                                @foreach(json_decode($item->stock_id) as $stockId)

                                                    {{  str_replace('@rt','GB',getStockDetatils($stockId)->name).","}}
                                                @endforeach
                                            @else
                                                @if(isset($item->stock))

                                                    {{str_replace('@rt','GB', $item->stock->name)}}
                                                @endif

                                            @endif


                                        </div>
                                    </div>
                                </div>


                                <div class="col-sm-6 col-md-6 mb-2">
                                    <div class="row">
                                        <div class="col-xs-5 col-sm-4 text-500">
                                            Total Cost price:
                                        </div>
                                        <div class="col-xs-7 col-sm-8 ">
                                            {{ money_format($totalCosts) }}
                                        </div>


                                    </div>
                                </div>

                                <div class="col-sm-6 col-md-6 mb-2">

                                    <div class="row">
                                        <div class="col-xs-5 col-md-6 col-lg-4 text-500">
                                            Colour:
                                        </div>

                                        <div class="col-xs-7 col-md-6 col-lg-8">

                                            @if($item->quantity>1 && !is_null(json_decode($item->stock_id)))

                                                @foreach(json_decode($item->stock_id) as $stockId)

                                                    {{getStockDetatils($stockId)->colour.","}}

                                                @endforeach

                                            @else
                                                @if(isset($item->stock))
                                                    {{$item->stock->colour}}
                                                @endif
                                            @endif

                                        </div>
                                    </div>


                                </div>

                                <div class="col-sm-6 col-md-6 mb-2">
                                    <div class="row">
                                        <div class="col-xs-5 col-md-6 col-lg-4 text-500">
                                            Vat Type:
                                        </div>

                                        <div class="col-xs-7 col-md-6 col-lg-8">
                                            @if($item->quantity>1 && !is_null(json_decode($item->stock_id)))

                                                @foreach(json_decode($item->stock_id) as $stockId)

                                                    {{getStockDetatils($stockId)->vat_type.","}}

                                                @endforeach

                                            @else

                                                @if(isset($item->stock))
                                                    {{$item->stock->vat_type}}
                                                @endif
                                            @endif

                                        </div>
                                    </div>
                                </div>

                            </div>
                            <div class="row">

                                <div class="col-sm-6 col-md-6 mb-2">
                                    <div class="row">
                                        <div class="col-xs-5 col-sm-4 text-500">
                                            Capacity:
                                        </div>
                                        <div class="col-xs-7 col-sm-8 ">
                                            @if($item->quantity>1 && !is_null(json_decode($item->stock_id)))

                                                @foreach(json_decode($item->stock_id) as $stockId)

                                                    {{getStockDetatils($stockId)->capacity_formatted.","}}

                                                @endforeach

                                            @else
                                                @if(isset($item->stock))
                                                    {{$item->stock->capacity_formatted}}
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                </div>


                                <div class="col-sm-6 col-md-6 mb-2">
                                    <div class="row">

                                        <div class="col-xs-5 col-sm-4 text-500">
                                            Allocated:
                                        </div>
                                        <div class="col-xs-7 col-sm-8 ">
                                            {{--@if(($item->stock))--}}
                                            {{--<span class="text-success"><strong>Yes</strong></span>--}}
                                            {{--@else--}}
                                            {{--<span class="text-danger"><strong>No</strong></span>--}}
                                            {{--@endif--}}
                                            @if($item->quantity>1)

                                                @if(!is_null($eBayOrder->EbayOrderItems[0]->stock_id))
                                                {{count(json_decode($eBayOrder->EbayOrderItems[0]->stock_id))}}
                                                @endif
                                            @else
                                                @if(($item->stock))
                                                    {{$item->quantity}}
                                                @endif

                                            @endif
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <div class="row">

                                <div class="col-sm-6 col-md-6 mb-2">
                                    <div class="row">
                                        <div class="col-xs-5 col-md-6 col-lg-4 text-500">
                                            Grade:
                                        </div>

                                        <div class="col-xs-7 col-md-6 col-lg-8">

                                            @if($item->quantity>1 && !is_null(json_decode($item->stock_id)))

                                                @foreach(json_decode($item->stock_id) as $stockId)

                                                    {{getStockDetatils($stockId)->grade.","}}

                                                @endforeach

                                            @else

                                                @if(isset($item->stock))
                                                    {{$item->stock->grade}}
                                                @endif
                                            @endif

                                        </div>
                                    </div>
                                </div>

                                <div class="col-sm-6 col-md-6 ">
                                    <table class="table table-responsive" style="margin-bottom:0px !important;">
                                        <tr>
                                            <td class="cut-tr">Profit:</td>
                                            <td class="cut-tr">
                                                @if(!is_null($item->stock))
                                                    @if(isset($item->stock) || count(json_decode($item->stock_id))>0)
                                                        {{money_format($profit)}}
                                                    @endif
                                               @endif
                                            </td>
                                            <td class="cut-tr">Profit %:</td>
                                            <td class="cut-tr">

                                                <?php


                                                if ($item->tax_percentage * 100 > 0 || !$item->tax_percentage * 100 && $vatType === "Standard") {
                                                    //  $profit = $item->stock->total_price_ex_vat ? ($item->stock->profit / $item->stock->total_price_ex_vat) * 100 : 0;
                                                    $profitPer = $vatExValue ? ($profit / $vatExValue) * 100 : 0;
                                                } else {
                                                    //  $profit = $item->stock->sale_price ? ($item->stock->profit / $item->stock->sale_price) * 100 : 0;

                                                    $salesPrice = $item['individual_item_price'] * $item['quantity'];
                                                    $profitPer = $item['individual_item_price'] ? ($profit / $salesPrice) * 100 : 0;
                                                }
                                                ?>


                                                @if(!is_null($item->stock))
                                                        @if(isset($item->stock)|| count(json_decode($item->stock_id))>0)

                                                            {{number_format($profitPer,2)."%"}}
                                                        @endif
                                                @endif



                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="cut-tr">True Profit:</td>
                                            <td class="cut-tr">
                                                @if(!is_null($item->stock))
                                                    @if(isset($item->stock) || count(json_decode($item->stock_id))>0)
                                                        {{money_format($trueProfit)}}
                                                    @endif
                                                @endif
                                            </td>

                                            <td class="cut-tr"> True Profit %:</td>
                                            <td class="cut-tr">
                                                <?php
                                                if(!is_null($item->stock)){
                                                        if ($item->stock || count(json_decode($item->stock_id)) > 0)
                                                        {
                                                            if ($item->tax_percentage * 100 > 0 || !$item->tax_percentage * 100 && $vatType === "Standard")
                                                            {
                                                                $trueProfitPer = $vatExValue ? ($trueProfit / $vatExValue) * 100 : 0;
                                                            } else {
                                                                $salesPrice = $item['individual_item_price'] * $item['quantity'];
                                                                $trueProfitPer = $item['individual_item_price'] ? ($trueProfit / $salesPrice) * 100 : 0;
                                                            }
                                                        }
                                                }

                                                ?>


                                                @if(!is_null($item->stock))
                                                        @if(isset($item->stock) ||
                                                            count(json_decode($item->stock_id))>0)
                                                            {{number_format($trueProfitPer,2)."%"}}
                                                        @endif
                                                @endif

                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-sm-6 col-md-6 mb-2">
                                    <div class="row">
                                        <div class="col-xs-5 col-sm-4 text-500">
                                            Condition:
                                        </div>
                                        <div class="col-xs-7 col-sm-8 ">

                                            @if($item->quantity>1 && !is_null(json_decode($item->stock_id)))

                                                @foreach(json_decode($item->stock_id) as $stockId)

                                                    {{getStockDetatils($stockId)->condition.","}}

                                                @endforeach

                                            @else

                                                @if(isset($item->stock))
                                                    {{$item->stock->condition}}
                                                @endif
                                            @endif

                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">

                                <div class="col-sm-6 col-md-6 mb-2">
                                    <div class="row">
                                        <div class="col-xs-5 col-sm-4 text-500">
                                            IMEI/serial/RCT ref/Name:
                                        </div>
                                        <div class="col-xs-7 col-sm-8 ">


                                            <div class="form-group">
                                                <div class="input-group">
                                                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                                    <input type="hidden" name="eBay_order_id" value="{{$item->id}}"
                                                           id="ebay_order_{{$item->id}}">
                                                    <input type="hidden" class="code" name="verified_code">
                                                    <?php
                                                    $sku = '';

                                                    if ($item->quantity > 1 && !is_null(json_decode($item->stock_id))) {

                                                    } else {
                                                        if (isset($item->stock)) {

                                                            if ($item->stock->imei !== "") {
                                                                $sku = $item->stock->imei;
                                                            } else if ($item->stock->serial !== "") {
                                                                $sku = $item->stock->serial;
                                                            } else {
                                                                $sku = $item->stock->sku;
                                                            }
                                                        }
                                                    }

                                                    ?>


                                                    <?php
                                                    $checkDisabled=false;
                                                    if(!is_null($item->stock_id))
                                                    {

                                                        if($item->quantity>1){
                                                            if(isset($eBayOrder->Newsale) || count(json_decode($item->stock_id,TRUE))>$item->quantity){
                                                                $checkDisabled=true;
                                                            }
                                                        }


                                                    }

                                                    ?>
                                                    <input type="text" name="imei_{{$item->id}}" value="{{$sku}}"
                                                           class="form-control value" id="{{$item->id}}"
                                                           @if($checkDisabled) disabled @endif>
                                                    <div id="countryList_{{$item->id}}"></div>
                                                    <span class="input-group-btn">

                                                    <button class="btn  @if($item->stock) btn-success @else btn-primary @endif addStock"
                                                            id="btn_{{$item->id}}"
                                                            @if(isset($eBayOrder->Newsale)) disabled @endif>
                                                        <i class="fa fa-check"></i>

                                                    </button>


                                                            <input type="hidden" name="ebay_item_id"
                                                                   value="{{$item->id}}">
                                                         <button class="btn  @if($item->stock) btn-danger @else btn-danger @endif delete_Stock"
                                                                 id="delete_{{$item->id}}"
                                                                 @if(isset($eBayOrder->Newsale)) disabled @endif>

                                                               <i class="fa fa-remove"></i>
                                                        </button>

                                                            <input type="hidden" id="imei">
									            </span>
                                                </div>

                                            </div>


                                        </div>
                                    </div>

                                </div>
                                <div class="col-sm-6 col-md-6 mb-2">
                                    <div class="row">
                                        <div class="col-xs-5 col-sm-4 text-500">
                                            Phone Check Status
                                        </div>
                                        <div class="col-xs-7 col-sm-8 ">

                                            @if($item->quantity>1 && !is_null(json_decode($item->stock_id)))

                                                @foreach(json_decode($item->stock_id) as $stockId)

                                                    {{getStockDetatils($stockId)->test_status.","}}
                                                    <?php
                                                    array_push($testStatusList, getStockDetatils($stockId)->test_status)
                                                    ?>

                                                @endforeach

                                                <input type="hidden" value="{{json_encode($testStatusList)}}"
                                                       id="test_status">

                                            @else
                                                @if(isset($item->stock))


                                                    <strong>
                                                        {{$item->stock->test_status}}
                                                    </strong>
                                                    <?php
                                                    array_push($testStatusList, $item->stock->test_status)
                                                    ?>
                                                    <input type="hidden" value="{{json_encode($testStatusList)}}"
                                                           id="test_status">

                                                    <input type="hidden" value="{{$item->stock->network}}"
                                                           id="network">
                                                @endif
                                            @endif


                                        </div>
                                    </div>

                                </div>

                            </div>

                            <hr/>
                        </div>

                        @endforeach

                        <div class="row mb-2">
                            <div class="col-md-3">
                            </div>
                            <div class="col-md-9">

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="row mb-2">
                                            <div class="col-xs-5 col-md-6 col-lg-4 text-500">
                                                Sale date:
                                            </div>
                                            <div class="col-xs-7 col-md-6 col-lg-4">
                                                @if(!empty($eBayOrder['sale_date']))
                                                    {{date('d-m-Y',strtotime($eBayOrder['sale_date']))}}
                                                @endif
                                            </div>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-xs-5 col-md-6 col-lg-4 text-500">
                                                Payment Date:
                                            </div>
                                            <div class="col-xs-7 col-md-6 col-lg-4 ">
                                                @if(!empty($eBayOrder['paid_on_date']))
                                                    {{date('d-m-Y',strtotime($eBayOrder['paid_on_date']))}}
                                                @endif
                                            </div>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-xs-5 col-md-6 col-lg-4 text-500">
                                                Payment Method:
                                            </div>
                                            <div class="col-xs-7 col-md-6 col-lg-4 ">
                                                @if(!empty($eBayOrder['payment_method']))
                                                    {{$eBayOrder['payment_method']."(".$eBayOrder['payment_type'].")"}}
                                                @endif
                                            </div>
                                        </div>

                                        <div class="row mb-2">
                                            <div class="col-xs-5 col-md-6 col-lg-4 text-500">
                                                Order Status:
                                            </div>
                                            <div class="col-xs-7 col-md-6 col-lg-8">
                                                <div class="input-group">
                                                    <select class="form-control" id="status"
                                                            @if($eBayOrder['status'] !=EbayOrders::STATUS_NEW) disabled @endif>
                                                        @foreach($statusList as $status)
                                                            <option value="{{$status}}"
                                                                    @if($eBayOrder['status']==$status) selected @endif>
                                                                {{ucfirst($status)}}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mb-2"></div>
                                        <div class="row mb-2"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="row mb-2">
                                            <div class="col-xs-5 col-md-6 col-lg-4 text-500">
                                                P&P:
                                            </div>
                                            <div class="col-xs-7 col-md-6 col-lg-8">
                                                @if($eBayOrder['currency_code']=="GBP")
                                                    <i class="fa fa-gbp"></i>
                                                @elseif($eBayOrder['currency_code']=="EUR")
                                                    <i class="fa fa-usd" aria-hidden="true"></i>
                                                @endif
                                                {{number_format($eBayOrder['postage_and_packaging'],2)}}
                                            </div>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-xs-5 col-md-6 col-lg-4 text-500">
                                                {{--Total Sell Price Ex Vat:--}}
                                                Order Total inc P&P (ex Vat):<br>

                                            </div>
                                            <div class="col-xs-7 col-md-6 col-lg-8">

                                                <div class="input-group">

                                                    {{  money_format($totalExVatPrice)}}

                                                </div>
                                            </div>

                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-xs-5 col-md-6 col-lg-4 text-500">
                                                Order Total inc P&P
                                            </div>
                                            <div class="col-xs-7 col-md-6 col-lg-8">
                                                {{--                                                @if($eBayOrder['currency_code']=="GBP")--}}
                                                {{--                                                    <i class="fa fa-gbp"></i>--}}
                                                {{--                                                @elseif($eBayOrder['currency_code']=="EUR")--}}
                                                {{--                                                    <i class="fa fa-usd" aria-hidden="true"></i>--}}
                                                {{--                                                @endif--}}
                                                <?php
                                                $discount = !is_null($eBayOrder['total_discount']) || !$eBayOrder['total_discount'] ? $eBayOrder['total_discount'] : 0;

                                                $res = !is_null($discount)? preg_replace('/-+/', '', $discount):0;
                                                ?>

                                                {{money_format(floatval($eBayOrder['total_price'])+floatval($eBayOrder['postage_and_packaging']) -$res)}}


                                                @if($res>0)
                                                    <i class="fa fa-info-circle" aria-hidden="true"
                                                       title="Total Price With Discount"></i>
                                                    F
                                                @endif

                                            </div>

                                        </div>
                                        <div class="row mb-2">

                                            <div class="col-xs-5 col-md-6 col-lg-4 text-500">
                                                Total Costs:
                                            </div>
                                            <div class="col-xs-7 col-md-6 col-lg-8">
                                                <div class="input-group">
                                                    {{  money_format($totalCostsFinal)}}
                                                </div>
                                            </div>


                                        </div>

                                        <div class="row mb-2">
                                            <div class="col-sm-11 col-md-11 mb-2">
                                                <table class="table table-responsive">
                                                    <tr>
                                                        <td class="cut-tr">Total Profit:</td>
                                                        <td class="cut-tr">{{ $totalProfit}}</td>
                                                        <td class="cut-tr"> Total Profit%:</td>

                                                        <td class="cut-tr">{{  $totalProfitPercentage."%"}}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="cut-tr">Total True Profit:</td>
                                                        <td class="cut-tr">{{  money_format($totalTrueProfit)}}</td>
                                                        <td class="cut-tr"> Total True Profit%:</td>
                                                        <td class="cut-tr"> {{  $totalTrueProfitPercentage."%"}}</td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                    <div class="panel panel-default page-break">
                        <div class="panel-heading"><strong>Delivery Information:</strong></div>
                        <div class="panel-body">
                            <div class="row">
                                <div class="col-sm-6 col-md-6 mb-2">
                                    <div class="row">
                                        <div class="col-xs-5 col-lg-4 text-500">
                                            Ship To Name:
                                        </div>
                                        <div class="col-xs-7 col-lg-8">
                                            {{$eBayOrder['post_to_name']}}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-md-6 mb-2">
                                    <div class="row">
                                        <div class="col-xs-5 col-lg-4 text-500">
                                            Ship To Company Name:
                                        </div>
                                        <div class="col-xs-7 col-lg-8">
                                            {{$eBayOrder['shipping_address_company_name']}}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6 col-md-6 mb-2">
                                    <div class="row">
                                        <div class="col-xs-5 col-lg-4 text-500">
                                            Ship To Address 1:
                                        </div>
                                        <div class="col-xs-7 col-lg-8">
                                            {{$eBayOrder['post_to_address_1']}}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-md-6 mb-2">
                                    <div class="row">
                                        <div class="col-xs-5 col-lg-4 text-500">
                                            Ship To Address 2:
                                        </div>
                                        <div class="col-xs-7 col-lg-8">
                                            {{$eBayOrder['post_to_address_2']}}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6 col-md-6 mb-2">
                                    <div class="row">
                                        <div class="col-xs-5 col-lg-4 text-500">
                                            Ship To City:
                                        </div>
                                        <div class="col-xs-7 col-lg-8">
                                            {{$eBayOrder['post_to_city']}}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-md-6 mb-2">
                                    <div class="row">
                                        <div class="col-xs-5 col-lg-4 text-500">
                                            Ship To County:
                                        </div>
                                        <div class="col-xs-7 col-lg-8">
                                            {{$eBayOrder['post_to_county']}}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6 col-md-6 mb-2">
                                    <div class="row">
                                        <div class="col-xs-5 col-lg-4 text-500">
                                            Ship To Postcode:
                                        </div>
                                        <div class="col-xs-7 col-lg-8">
                                            {{strtoupper($eBayOrder['post_to_postcode'])}}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-md-6 mb-2">
                                    <div class="row">
                                        <div class="col-xs-5 col-lg-4 text-500">
                                            Ship To Country:
                                        </div>
                                        <div class="col-xs-7 col-lg-8">
                                            {{$eBayOrder['post_to_country']}}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6 col-md-6 mb-2">
                                    <div class="row">
                                        <div class="col-xs-5 col-lg-4 text-500">
                                            Ship To Service Information:
                                        </div>
                                        <div class="col-xs-7 col-lg-8">
                                            {{$eBayOrder['delivery_service']}}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-md-6 mb-2">
                                    <div class="row">
                                        <div class="col-xs-5 col-lg-4 text-500">
                                            Ship To Dispatched on date:
                                        </div>
                                        <div class="col-xs-7 col-lg-8">
                                            @if(!empty($eBayOrder['post_by_date']))
                                                {{date('d-m-Y',strtotime($eBayOrder['post_by_date']))}}
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-sm-6 col-md-6 mb-2">
                                    <div class="row">
                                        <div class="col-xs-5 col-lg-4 text-500">
                                            Ship To Tracking:
                                        </div>
                                        <div class="col-xs-7 col-lg-8">
                                            @if(strlen($eBayOrder['tracking_number']) == 14)
                                                <a href="https://www.dpd.co.uk/apps/tracking/?reference={{$eBayOrder['tracking_number']}}"
                                                   target="_blank">{{$eBayOrder['tracking_number']}}</a>
                                            @elseif(strlen($eBayOrder['tracking_number']) == 16)
                                                <a href="https://new.myhermes.co.uk/track.html#/parcel/{{$eBayOrder['tracking_number']}}"
                                                   target="_blank">{{$eBayOrder['tracking_number']}}</a>
                                            @else
                                                <a href="https://www.royalmail.com/track-your-item#/tracking-results/.{{$eBayOrder['tracking_number']}}"
                                                   target="_blank">{{$eBayOrder['tracking_number']}}</a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-md-6 mb-2">
                                    <div class="row">
                                        <div class="col-xs-5 col-lg-4 text-500">
                                            Ship To Country Code:
                                        </div>
                                        <div class="col-xs-7 col-lg-8">
                                            {{$eBayOrder['shipping_address_country_code']}}

                                        </div>
                                    </div>
                                </div>


                                <div class="col-sm-6 col-md-6 mb-2">
                                    <div class="row">
                                        <div class="col-xs-5 col-lg-4 text-500">
                                            Customer Email:
                                        </div>
                                        <div class="d-flex col-xs-7 col-lg-6">
                                            <form action="{{route('admin.ebay.update-contact-info')}}" method="post">
                                                <input type="hidden" name="id" value="{{$eBayOrder['id']}}">
                                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                                <div class="input-group">
                                                    <input type="text" name="email"
                                                           value="{{$eBayOrder['buyer_email']}}"
                                                           class="form-control value">
                                                    <span class="input-group-btn">
			                                            <button class="btn   btn-success"><i
                                                                class="fa fa-check"></i></button>
		                                            </span>
                                                </div>

                                            </form>

                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-md-6 mb-2">
                                    <div class="row">
                                        <div class="col-xs-5 col-lg-4 text-500">
                                            Phone Number
                                        </div>
                                        <div class="col-xs-7 col-lg-8">


                                            <form action="{{route('admin.ebay.update-contact-info')}}" method="post">
                                                <input type="hidden" name="id" value="{{$eBayOrder['id']}}">
                                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                                <div class="input-group">
                                                    <input type="text" name="phone_number"
                                                           value="{{$eBayOrder['billing_phone_number']}}"
                                                           class="form-control value">
                                                    <span class="input-group-btn">
			                                            <button class="btn   btn-success"><i
                                                                class="fa fa-check"></i></button>
		                                            </span>
                                                </div>

                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="panel panel-default page-break">
                        <div href="#ebay-order-logs" data-toggle="collapse"
                             class="panel-heading c-pointer heading-collapse collapsed">
                            <strong>eBay Order Logs:</strong>
                            <i class="fa fa-plus icon-collapse" aria-hidden="true"></i>
                        </div>
                        <div class="panel-body collapse" id="ebay-order-logs">
                            <table class="table table-bordered">
                                <thead>
                                <tr>
                                    <th>Log</th>
                                    <th>Date</th>
                                </tr>
                                </thead>
                                <tbody>
                                {{--                                @forelse($orderLog as $log)--}}
                                {{--                                    <tr>--}}
                                {{--                                        <td class="word-break-all">{!! nl2br($log->content) !!}</td>--}}
                                {{--                                        <td>{{ $log->created_at->format("d M Y H:i:s") }}</td>--}}
                                {{--                                    </tr>--}}
                                {{--                                @empty--}}
                                {{--                                    <tr>--}}
                                {{--                                        <td colspan="3">No record found</td>--}}
                                {{--                                    </tr>--}}
                                {{--                                @endforelse--}}
                                </tbody>
                            </table>
                            {{--                            {!! $orderLog->appends(Request::all())->render() !!}--}}
                        </div>
                    </div>
            </div>

            <input type="hidden" value="{{json_encode($vatTypeFlag)}}" id="v_type">

        </div>
    </div>
@endsection
@section('scripts')
    <script>

        $(document).ready(function () {

            $('.value').on('keyup', function () {
                var query = $(this).val();
                var id = this.id;


                if (query != '') {

                    $.ajax({
                        url: "{{ route('stock.info') }}",
                        method: 'POST',
                        data: {
                            "_token": "{{ csrf_token() }}",
                            value: query,
                            id: id

                        },
                        success: function (data) {
                            $('#countryList_' + id).fadeIn();
                            $('#countryList_' + id).html(data);

                        }
                    });
                }
            });

            $(document).on('click', 'li', function () {
                var value = $(this).text().split(':')[1];
                var id = this.id;
                $('#' + id).val(value);
                $('#countryList_' + id).fadeOut();
                $("")
            });

        });


        $(".edit").on('click', function (e) {
            e.preventDefault();

            var id = this.id.split('_')[1];

            $("#rate_box_" + id).slideToggle();
        });
        $(".fg").on('click', function (e) {

            e.preventDefault();

            var id = this.id.split('_')[1];
            var rate = $("#" + "rate_" + id).val();


            $.ajax({
                url: "{{ route('admin.ebay.update-rate') }}",
                method: 'POST',
                data: {
                    "_token": "{{ csrf_token() }}",
                    id: id,
                    rate: rate,
                },
                success: function (data) {

                    location.reload();


                }
            });
        });


        $(".delete_Stock").on('click', function (e) {
            e.preventDefault();
            var id = this.id.split('_')[1];


            var r = confirm("Do you want unassigned stock");
            if (r == true) {
                $.ajax({
                    url: "{{ route('admin.ebay.unassigned-stock') }}",
                    method: 'POST',
                    data: {
                        "_token": "{{ csrf_token() }}",
                        id: id,
                    },
                    success: function (data) {


                        location.reload();


                    }
                });
            }


        })


        $(".addStock").on('click', function (e) {
            e.preventDefault();
            var id = this.id.split('_')[1];
            var imei = $("#" + id).val();
            var ebayOrder = $("#ebay_order_" + id).val();
            $.ajax({
                url: "{{ route('admin.ebay.assign-stock') }}",
                method: 'POST',
                data: {
                    "_token": "{{ csrf_token() }}",
                    id: id,
                    // code: code,
                    imei: imei,
                    eBay_order_id: ebayOrder

                },
                success: function (data) {


                    if (data.error) {
                        $("#error_message").slideDown();
                        $("#message").html(data.error);
                        return false
                    }

                    if (data.success) {
                        $("#message_success").show();
                    }


                    location.reload();


                }
            });

        });

        $("#addSelect2").on('click', function (e) {

            var testStatus = $("#test_status").val();
            var vatType = $("#v_type").val();
            var network=$("#network").val();
            const vatTypeArr = JSON.parse(vatType);
            e.preventDefault();

            console.log(vatTypeArr);
            var vatFlag = true;
            for (let i = 0; i < vatTypeArr.length; i++) {

                if (!vatTypeArr[i]) {
                    vatFlag = false;

                }

            }

            var modalConfirmVatFlag = function (callback) {
                $("#mi-modal").modal('show');
                $("#modal-btn-si").on("click", function () {
                    callback(true);
                    $("#mi-modal").modal('hide');
                });

                $("#modal-btn-no").on("click", function () {
                    callback(false);
                    $("#mi-modal").modal('hide');
                });
            };


            let text = "Same for type = marginal - prevent create sale for .20%";
            let networkMessage='One of the devices in this sale is locked to a network. Please unlock before dispatch.';
            if(confirm(networkMessage)){
                if (confirm) {
                    $("#verificationForm2").submit()
                }else{
                    false
                }

            }

            const myArr = JSON.parse(testStatus);
            if (!vatFlag) {
                if (confirm(text) == true) {
                    modalConfirmVatFlag(function (confirm2) {
                        if (confirm) {
                            for (let i = 0; i < myArr.length; i++) {
                                var text2 = "This Item Is Not In Testing Complete Status";
                                if (myArr[i] != "Complete") {
                                    if (confirm(text2) == true) {
                                        $("#verificationForm2").submit()
                                    } else {
                                        return false;
                                    }

                                } else {
                                    $("#verificationForm2").submit()
                                }
                            }
                        } else {
                            $("#result").html("NO CONFIRMADO");
                        }
                    });

                }


            } else {

                var text2 = "This Item Is Not In Testing Complete Status"
                for (let i = 0; i < myArr.length; i++) {
                    if (myArr[i] != "Complete") {
                        if (confirm(text2) == true) {
                            $("#verificationForm2").submit()
                        } else {
                            return false;
                        }

                    } else {
                        $("#verificationForm2").submit()
                    }
                }

            }


        });

        $("#addSales").on('click', function (e) {
            var testStatus = $("#test_status").val()
            var vatType = $("#v_type").val();
            const vatTypeArr = JSON.parse(vatType);
            const myArr = JSON.parse(testStatus);
            e.preventDefault();

            var vatFlag = true;
            for (let i = 0; i < vatTypeArr.length; i++) {

                if (!vatTypeArr[i]) {
                    vatFlag = false;

                }

            }

            var modalConfirmVatFlag = function (callback) {
                $("#mi-modal").modal('show');
                $("#modal-btn-si").on("click", function () {
                    callback(true);
                    $("#mi-modal").modal('hide');
                });

                $("#modal-btn-no").on("click", function () {
                    callback(false);
                    $("#mi-modal").modal('hide');
                });
            };

            let textVat = "Same for type = marginal - prevent create sale for .20%";
            if (!vatFlag) {

                if (confirm(textVat) == true) {

                    var modalConfirm = function (callback) {
                        $("#mi-modal").modal('show');
                        $("#modal-btn-si").on("click", function () {
                            callback(true);
                            $("#mi-modal").modal('hide');
                        });

                        $("#modal-btn-no").on("click", function () {
                            callback(false);
                            $("#mi-modal").modal('hide');
                        });
                    };

                    let text = "This Item Is Not In Testing Complete Status";
                    for (let i = 0; i < myArr.length; i++) {
                        if (myArr[i] != "Complete") {
                            if (confirm(text) == true) {
                                modalConfirm(function (confirm) {
                                    if (confirm) {
                                        var code = $("#verification_code").val();
                                        $("#code").val(code);
                                        $("#verificationForm").submit()
                                    } else {
                                        $("#result").html("NO CONFIRMADO");
                                    }
                                });
                            } else {
                                $("#mi-modal").modal('hide');
                            }


                        } else {
                            modalConfirm(function (confirm) {
                                if (confirm) {
                                    var code = $("#verification_code").val();
                                    $("#code").val(code);
                                    $("#verificationForm").submit()
                                } else {
                                    $("#result").html("NO CONFIRMADO");
                                }
                            });
                        }

                    }


                }

            }
            // var modalConfirm = function (callback) {
            //     $("#mi-modal").modal('show');
            //     $("#modal-btn-si").on("click", function () {
            //         callback(true);
            //         $("#mi-modal").modal('hide');
            //     });
            //
            //     $("#modal-btn-no").on("click", function () {
            //         callback(false);
            //         $("#mi-modal").modal('hide');
            //     });
            // };
            //
            // let text = "This Item Is Not In Testing Complete Status";
            //  for (let i = 0; i < myArr.length; i++) {
            //      if(myArr[i]!="Complete"){
            //          if (confirm(text) == true) {
            //              modalConfirm(function (confirm) {
            //                  if (confirm) {
            //                      var code = $("#verification_code").val();
            //                      $("#code").val(code);
            //                      $("#verificationForm").submit()
            //                  } else {
            //                      $("#result").html("NO CONFIRMADO");
            //                  }
            //              });
            //          }else{
            //              $("#mi-modal").modal('hide');
            //          }
            //
            //
            //
            //      }else{
            //          modalConfirm(function (confirm) {
            //              if (confirm) {
            //                  var code = $("#verification_code").val();
            //                  $("#code").val(code);
            //                  $("#verificationForm").submit()
            //              } else {
            //                  $("#result").html("NO CONFIRMADO");
            //              }
            //          });
            //      }
            //
            //  }
            // for (let i = 0; i < myArr.length; i++) {
            //
            //     let text = "This Item Is Not In Testing Complete Status";
            //     if(myArr[i]!="Complete"){
            //         if (confirm(text) == true) {
            //             $(".verificationForm2").submit()
            //         }else{
            //             return false;
            //         }
            //     }else{
            //         $(".verificationForm2").submit()
            //     }
            //
            // }

            // for (let i = 0; i < myArr.length; i++) {
            //
            // }

            // if(testStatus!="Complete"){
            //     if (confirm(text) == true) {
            //         modalConfirm(function (confirm) {
            //             if (confirm) {
            //                 var code = $("#verification_code").val();
            //                 $("#code").val(code);
            //                 $("#verificationForm").submit()
            //             } else {
            //                 $("#result").html("NO CONFIRMADO");
            //             }
            //         });
            //     }
            // }else{
            //         modalConfirm(function (confirm) {
            //             if (confirm) {
            //                 var code = $("#verification_code").val();
            //                 $("#code").val(code);
            //                 $("#verificationForm").submit()
            //             } else {
            //                 $("#result").html("NO CONFIRMADO");
            //             }
            //         });
            // }


        });

        $(document).ready(function () {
            $('.ebay-owner').on('change', function () {
                var item_id = $(this).attr("data-item-id");
                var owner = $(this).val();

                $.ajax({
                    url: "{{ route('ebay.owner.update') }}",
                    method: 'put',
                    data: {
                        item_id: item_id,
                        owner: owner
                    },
                    success: function (result) {
                        location.reload(true);

                    }
                });

            });

            $('.sale_type').on('change', function () {
                var item_id = $(this).attr("data-item-id");
                var sale_type = $(this).val();

                $.ajax({
                    url: "{{ route('ebay.sale-type.update') }}",
                    method: 'put',
                    data: {
                        item_id: item_id,
                        sale_type: sale_type
                    },
                    success: function (result) {
                        location.reload(true);

                    }
                });

            });
            $('#status').on('change', function () {
                var id = $("#order_id").val();
                var status = $("#status").val();
                $.ajax({
                    url: "{{ route('status-update') }}",
                    method: 'post',
                    data: {
                        id: id,
                        status: status
                    },
                    success: function (data) {
                        $("#message").show();
                        setTimeout(function () {
                            window.location.reload(1);
                        }, 700);
                    }
                });

            });
            $('.owner-select2').select2({
                placeholder: "Select Owner",
            });
        });
    </script>
@endsection
