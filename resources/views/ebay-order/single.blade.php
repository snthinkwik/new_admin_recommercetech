<?php
use App\Models\EbayOrderItems;
use App\Models\EbayOrders;
use App\Models\Stock;
$ownerList =EbayOrderItems::getAvailableOwnerWithKeys();
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
            if (count($item->stock)>0) {
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
            <div class="col-lg-10">
                <a class="btn btn-default hide-print" href="{{ route('admin.ebay-orders') }}">Back to
                    list</a>
            </div>
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
                               @if(!$stockAvailable || count($eBayOrder->Newsale) ) disabled @endif id="addSales">

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
                               @if(!$stockAvailable || count($eBayOrder->Newsale) ) disabled @endif >

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
                                    if ($vatType === \App\Stock::VAT_TYPE_MAG) {
                                        array_push($vatTypeFlag, true);
                                    } else {
                                        array_push($vatTypeFlag, false);
                                    }
                                } else {


                                    if ($vatType === \App\Stock::VAT_TYPE_STD) {
                                        array_push($vatTypeFlag, true);
                                    } else {
                                        array_push($vatTypeFlag, false);
                                    }

                                }




                            }

                            if($eBayOrder->platform === \App\Stock::PLATFROM_MOBILE_ADVANTAGE || $eBayOrder->platform === \App\Stock::PLATFROM_EBAY ){
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
                                if ($vatType === \App\Stock::VAT_TYPE_MAG) {
                                    array_push($vatTypeFlag, true);
                                } else {
                                    array_push($vatTypeFlag, false);
                                }
                            } else {
                                if ($vatType === \App\Stock::VAT_TYPE_STD) {
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

                            if($eBayOrder->platform===\App\Stock::PLATFROM_MOBILE_ADVANTAGE || $eBayOrder->platform === \App\Stock::PLATFROM_EBAY )
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
                                $totalPurchasePrice += count(json_decode($item->stock_id)) ? $purchasePrice : $item->stock->purchase_price;
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
                <div>
                    {{$totalExVatPrice}}
                </div>


                @endforeach
            </div>
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
