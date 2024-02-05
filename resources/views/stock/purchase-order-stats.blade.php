<?php
use App\Stock;
$countries = ['' => 'Please Select'] + Stock::getAvailablePurchaseCountriesWithKeys();
?>
@extends('app')

@section('title', 'Purchase order stats')

@section('content')

    <div class="container-fluid">

        @include('messages')

        <h1>Purchase order stats</h1>
        <div class="row">
            <div class="col-md-3">
                {!! BsForm::open(['route' => 'stock.purchase-order-stats', 'method' => 'get', 'class' => 'form-inline mb15']) !!}
                {!!
                    BsForm::groupText(
                        'purchase_order_number', null, ['placeholder' => 'Purchase order number', 'size' => 30], ['label' => false]
                    )
                !!}
                {!! BsForm::submit('Show stats', ['class' => 'btn btn-default']) !!}
                {!! BsForm::close(); !!}
            </div>
            <div class="col-md-3">
                {!! BsForm::open(['route' => 'stock.purchase-order-update-ps-model', 'method' => 'post', 'class' => 'form-inline mb15']) !!}
                <div class="form-group">
                    <div class="input-group">
                        <span class="input-group-addon">P/S Model</span>
                        <input type="hidden" name="purchase_order_number"
                               value="{{Request::input('purchase_order_number')}}">
                        {!! BsForm::select('ps_model',[''=>'Please Select P/S Model','1'=>'Yes','0'=>'No'], $psModel, ['required' => 'required']) !!}
                        <span class="input-group-btn">{!! BsForm::submit('Update', ['class' => 'confirmed', 'data-confirm' => 'Items P/S Model will be updated']) !!}</span>
                    </div>
                </div>

                {!! BsForm::close(); !!}
            </div>
        </div>

        @if (Request::input('purchase_order_number') && !$stats)
            <div class="alert alert-warning">Nothing found for "{{ Request::input('purchase_order_number') }}"</div>
        @elseif ($stats)
            <a class="btn btn-default"
               href="{{ route('stock.purchase-order-stats-export', ['number' => Request::input('purchase_order_number')]) }}"><i
                        class="fa fa-download"></i> Export</a>

            <a class="btn btn-default"
               href="{{ route('stock.purchase-order-stats-phone-diagnostics-export', ['number' => Request::input('purchase_order_number')]) }}"><i
                        class="fa fa-download"></i> Export Phone Diagnostics</a>

            <a class="btn btn-default"
               href="{{ route('stock.purchase-order-stats-phone-diagnostics-export-all', ['number' => Request::input('purchase_order_number')]) }}"><i
                        class="fa fa-download"></i> Export Phone Diagnostics (All Items)</a>

            <a class="btn btn-default"
               href="{{ route('stock.purchase-order-stats-phone-diagnostics-export-missing-notes', ['number' => Request::input('purchase_order_number')]) }}"><i
                        class="fa fa-download"></i> Export items missing engineer notes</a>

            <a class="btn btn-default"
               href="{{ route('stock.purchase-order-stats-export-stats', ['purchase_order_number' => Request::input('purchase_order_number')]) }}"><i
                        class="fa fa-download"></i> Stats PDF</a>

            {!! BsForm::open(['method' => 'post', 'route' => 'stock.purchase-order-update-purchase-country', 'class' => 'form-inline mt5']) !!}
            {!! BsForm::hidden('purchase_order_number', Request::input('purchase_order_number')) !!}
            <div class="form-group">
                <div class="input-group">
                    <span class="input-group-addon">Purchase Country</span>
                    {!! BsForm::select('purchase_country', $countries, null, ['required' => 'required']) !!}
                    <span class="input-group-btn">{!! BsForm::submit('Save', ['class' => 'confirmed', 'data-confirm' => 'Items Purchase Country will be updated']) !!}</span>
                </div>
            </div>
            {!! BsForm::close() !!}

            {!! BsForm::open(['method' => 'post', 'route' => 'stock.purchase-order-update-purchase-date', 'class' => 'form-inline mt5']) !!}
            {!! BsForm::hidden('purchase_order_number', Request::input('purchase_order_number')) !!}
            <div class="form-group">
                <div class="input-group">
                    <span class="input-group-addon">Purchase Date</span>
                    {!! BsForm::text('purchase_date', null, ['class' => 'required has-datepicker']) !!}
                    <span class="input-group-btn">
							{!! BsForm::submit('Save') !!}
						</span>
                </div>
            </div>
            {!! BsForm::close() !!}

            <table class="table">
                <caption>Statistics for "{{ Request::input('purchase_order_number') }}"</caption>
                <tbody>
                <tr class="active">
                    <th>No. Devices</th>
                    <td>{{ $stats['total'] }}</td>
                </tr>

                <tr class="active">
                    <th>Vat Type</th>
                    <td>{{ $stats['vat_type'] }}</td>
                </tr>

                <tr data-toggle="collapse" data-target="#items-sold" class="info-light">
                    <th>No. Items Sold</th>
                    <td>{{ count($items_sold) }}</td>
                </tr>
                <tr data-toggle="collapse" data-target="#items-to-sell" class="info-light">
                    <th>No. Items To Sell</th>
                    <td>{{ count($items_to_sell) }}</td>
                </tr>
                <tr data-toggle="collapse" data-target="#items-in-repair" class="info-light">
                    <th>No. Items in Repair</th>
                    <td>{{ count($items_in_repair) }}</td>
                </tr>
                <tr data-toggle="collapse" data-target="#items-returned" class="info-light">
                    <th>No. Items Returned</th>
                    <td>{{ $stats['total'] > 0 ? number_format(count($items_returned)/$stats['total']*100, 2) : '0.00%'}}
                        % ({{ count($items_returned) }}/{{ $stats['total'] }})
                    </td>
                </tr>
                <tr class="info-light">
                    <th>Devices Tested</th>
                    <td>{{ $tested }} out of {{ $stats['total_tested'] }}</td>
                </tr>

                <tr class="success-light">
                    <th>% fully working - no touch id</th>
                    <td>{{ number_format($stats['fully_working_no_touch_id'] / $stats['total'] * 100) }}%</td>
                </tr>
                <tr class="success-light">
                    <th>% fully working</th>
                    <td>{{ number_format($stats['fully_working'] / $stats['total'] * 100) }}%</td>
                </tr>
                <tr class="success-light">
                    <th>% minor fault</th>
                    <td>{{ number_format($stats['minor_fault'] / $stats['total'] * 100) }}%</td>
                </tr>
                <tr class="success-light">
                    <th>% major fault</th>
                    <td>{{ number_format($stats['major_fault'] / $stats['total'] * 100) }}%</td>
                </tr>
                <tr class="success-light">
                    <th>% no signs of life</th>
                    <td>{{ number_format($stats['broken'] / $stats['total'] * 100) }}%</td>
                </tr>

                <tr class="info-light">
                    <th>% grade A</th>
                    <td>{{ number_format($stats['condition_a'] / $stats['total'] * 100) }}%</td>
                </tr>
                <tr class="info-light">
                    <th>% grade B</th>
                    <td>{{ number_format($stats['condition_b'] / $stats['total'] * 100) }}%</td>
                </tr>
                <tr class="info-light">
                    <th>% grade C</th>
                    <td>{{ number_format($stats['condition_c'] / $stats['total'] * 100) }}%</td>
                </tr>
                <tr class="info-light">
                    <th>% grade D</th>
                    <td>{{ number_format($stats['condition_d'] / $stats['total'] * 100) }}%</td>
                </tr>
                <tr class="info-light">
                    <th>% grade E</th>
                    <td>{{ number_format($stats['condition_e'] / $stats['total'] * 100) }}%</td>
                </tr>

                <tr class="success-light">
                    <th>% unlocked</th>
                    <td>{{ number_format($stats['networks']['unlocked'] / $stats['total'] * 100) }}%</td>
                </tr>
                <tr class="success-light">
                    <th>% unknown</th>
                    <td>{{ number_format($stats['networks']['unknown'] / $stats['total'] * 100) }}%</td>
                </tr>
                <tr class="success-light">
                    <th>% Vodafone</th>
                    <td>{{ number_format($stats['networks']['vodafone'] / $stats['total'] * 100) }}%</td>
                </tr>
                <tr class="success-light">
                    <th>% EE</th>
                    <td>{{ number_format($stats['networks']['ee'] / $stats['total'] * 100) }}%</td>
                </tr>
                <tr class="success-light">
                    <th>% Three</th>
                    <td>{{ number_format($stats['networks']['three'] / $stats['total'] * 100) }}%</td>
                </tr>
                <tr class="success-light">
                    <th>% O2</th>
                    <td>{{ number_format($stats['networks']['o2'] / $stats['total'] * 100) }}%</td>
                </tr>
                <tr class="success-light">
                    <th>% other</th>
                    <td>{{ number_format($stats['networks']['other'] / $stats['total'] * 100) }}%</td>
                </tr>

                <tr class="info-light alert alert-info">
                    <th>{{\App\Stock::GRADE_FULLY_WORKING}}</th>
                    <td>A:{{$stats['fully_working_A']  }}<br>
                        B:{{$stats['fully_working_B']  }}<br>
                        C:{{$stats['fully_working_C']  }}<br>
                        D:{{$stats['fully_working_D']  }}<br>
                        E:{{$stats['fully_working_E']  }}

                    </td>
                </tr>

                <tr class="info-light alert alert-info">
                    <th>{{\App\Stock::GRADE_MINOR_FAULT}}</th>
                    <td>A:{{$stats['minor_fault_A']  }}<br>
                        B:{{$stats['minor_fault_B']  }}<br>
                        C:{{$stats['minor_fault_C']  }}<br>
                        D:{{$stats['minor_fault_D']  }}<br>
                        E:{{$stats['minor_fault_E']  }}

                    </td>
                </tr>
                <tr class="info-light alert alert-info">
                    <th>{{\App\Stock::GRADE_MAJOR_FAULT}}</th>
                    <td>A:{{$stats['major_fault_A']  }}<br>
                        B:{{$stats['major_fault_B']  }}<br>
                        C:{{$stats['major_fault_C']  }}<br>
                        D:{{$stats['major_fault_D']  }}<br>
                        E:{{$stats['major_fault_E']  }}

                    </td>
                </tr>
                <tr class="success-light">
                    <th>Total Purchase Price</th>
                    <td>{{ money_format(config('app.money_format'),$stats['purchase_price'])   }}</td>
                </tr>
                <tr class="success-light">
                    <th>Total Sales Price</th>
                    <td>{{ money_format(config('app.money_format'),$stats['sales_price']) }}</td>
                </tr>
                @if($stats['vat_type']===\App\Stock::VAT_TYPE_STD)
                    <tr class="success-light">
                        <th>Total Price Ex Vat</th>
                        <td>{{ money_format(config('app.money_format'),$stats['total_price_ex_vat']) }}</td>
                    </tr>
                @endif
                <tr class="success-light">
                    <th>Total Profit</th>
                    <td>{{ money_format(config('app.money_format'),$stats['total_profit']) }}</td>
                </tr>
                @if($stats['vat_type']===\App\Stock::VAT_TYPE_MAG)
                    <tr class="success-light">
                        <th>Total Vat</th>
                        <td>{{ money_format(config('app.money_format'),$stats['total_vat']) }}</td>
                    </tr>
                    <tr class="success-light">
                        <th>Total True Profit</th>
                        <td>{{ money_format(config('app.money_format'),$stats['total_true_profit']) }}</td>
                    </tr>
                @endif
                <tr class="success-light">
                    <th>Total Seller Fees</th>
                    <td>{{ money_format(config('app.money_format'),$stats['total_fees']) }}</td>
                </tr>
                <tr class="success-light">
                    <th>Total Est Net Profit</th>
                    <td>{{ money_format(config('app.money_format'),$stats['est_profit']) }}</td>
                </tr>
                <tr class="success-light">
                    <th>Total Est Net Profit %</th>
                    <td>{{  number_format($stats['est_profit_pre'],2) .'%' }}</td>
                </tr>
                </tbody>
            </table>
            <div class="panel panel-default">
                <div data-toggle="collapse" data-target="#items-sold" class="panel-heading">Items Sold <span
                            class="badge">{{ $items_total }}</span></div>
                <div class="panel-body collapse {{ Request::has('page')? 'in':'' }}" id="items-sold">
                    <table class="table table-condensed table-small">
                        <thead>
                        <tr>
                            <th>Ref</th>
                            <th>3rd-party ref</th>
                            <th>Name</th>
                            <th>Capacity</th>
                            <th>Colour</th>
                            <th>Supplier Condition</th>
                            <th>Recomm Condition</th>
                            <th>Supplier Grade</th>
                            <th>Recomm Grade</th>
                            <th>Network</th>
                            <th>Status</th>
                            <th>Shown To</th>
                            <th>Vat Type</th>
                            <th>Purchase date</th>
                            <th>Device Purchase Price</th>
                            <th>Total Purchase price</th>
                            <th>Sale price</th>
                            <th>Sale Price Ex Vat</th>
                            <th>Marginal Vat</th>
                            <th>True Profit</th>
                            <th>Fee's (Inc Shipping)</th>
                            <th>Est Net Profit</th>
                            <th>Est Net Profit%</th>

                            <th>PlatFrom</th>

                            <th class="text-center"><i class="fa fa-globe"></i></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($items_sold as $item)
                            <tr>
                                <td><a href="{{ route('stock.single', ['id' => $item->id]) }}">{{ $item->our_ref }}</a>
                                </td>
                                <td>{{ $item->third_party_ref }}</td>
                                <td>{{ str_replace( array('@rt'), 'GB', $item->name)   }}  </td>
                                <td>{{ $item->capacity_formatted }}</td>
                                <td>{{ $item->colour }}</td>
                                <td>{{$item->original_condition}}</td>
                                <td>{{ $item->condition }}</td>
                                <td>{{$item->original_grade}}</td>
                                <td>{{ $item->grade }}</td>
                                <td>{{ $item->network }}</td>
                                <td>{{ $item->status }}</td>
                                <td>{{ $item->shown_to }}</td>
                                <td>{{$item->vat_type}}</td>

                                <td>{{ $item->purchase_date ? $item->purchase_date->format('Y-m-d') : '' }}</td>
                                <td>{{money_format(config('app.money_format'),$item->purchase_price)}}</td>

                                <td>{{ money_format(config('app.money_format'),$item->total_cost_with_repair)  }}</td>
                                <td>{{money_format(config('app.money_format'),$item->sale_price)  }}</td>

                                <td>
                                    @if($item->vat_type===Stock::VAT_TYPE_STD)
                                        {{money_format(config('app.money_format'),$item->sale_price/1.2)  }}
                                    @else
                                        N/A
                                    @endif
                                </td>

                                <td>{{ money_format(config('app.money_format'),$item->marg_vat) }}</td>
                                <td>{{ money_format(config('app.money_format'), $item->true_profit) }}</td>
                                <td>
                                    <?php
                                    $shippingCostAvg=($item->sale->platform_fee + $item->sale->shipping_cost)/count($item->sale->stock);
                                    $delivery_charges=0;
                                    $finalShippingCost=0;

                                    if(!is_null($item->sale->delivery_charges)){
                                        $delivery_charges=($item->sale->delivery_charges/1.2)/count($item->sale->stock);

                                    }


                                    $finalShippingCost= $shippingCostAvg - $delivery_charges;

                                    ?>
                                    @if(!is_null($item->sale))
{{--                                        {{money_format(config('app.money_format'),$item->sale->platform_fee + $item->sale->shipping_cost)}}--}}
                                            {{money_format(config('app.money_format'),$finalShippingCost)}}
                                    @endif
                                </td>
                                <?php
                                $estProfit=$item->true_profit-$finalShippingCost
                                ?>
                                @if(count($item->sale)>0)
                                    @if(in_array($item->sale->platform,[Stock::PLATFROM_EBAY,Stock::PLATFROM_BACKMARCKET,Stock::PLATFROM_MOBILE_ADVANTAGE]))
                                        <td>{{ money_format(config('app.money_format'),$estProfit)}}</td>
                                    @else
                                        <td>{{  money_format(config('app.money_format'),$estProfit) }}</td>
                                    @endif
                                @endif
                                <?php
                                if ($item->vat_type === Stock::VAT_TYPE_MAG) {
                                    $estProfitPre = ($estProfit / $item->sale_price) * 100;
                                } else {
                                    $estProfitPre = ($estProfit / $item->total_price_ex_vat) * 100;
                                }


                                ?>

                                @if(count($item->sale)>0)
                                    @if(in_array($item->sale->platform,[Stock::PLATFROM_EBAY,Stock::PLATFROM_BACKMARCKET,Stock::PLATFROM_MOBILE_ADVANTAGE]))
                                        <td>
                                            {{number_format($estProfitPre,2)."%"}}
                                        </td>
                                    @else
                                        <td>{{ number_format($estProfitPre,2)."%" }}</td>
                                    @endif
                                @endif

                                <td>
                                    @if(!is_null($item->sale))
                                        {{$item->sale->platform}}
                                    @endif

                                </td>
                                <td><img src="{{ $item->purchase_country_flag }}" alt=""></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    <div id="stock-pagination-wrapper">{!! $items_sold->appends(Request::all())->render() !!}</div>
                </div>

            </div>

            <div class="panel panel-default">
                <div data-toggle="collapse" data-target="#items-to-sell" class="panel-heading">Items To Sell <span
                            class="badge">{{ count($items_to_sell) }}</span></div>
                <div class="panel-body collapse" id="items-to-sell">
                    <table class="table table-condensed table-small">
                        <thead>
                        <tr>
                        <tr>
                            <th>Ref</th>
                            <th>3rd-party ref</th>
                            <th>Name</th>
                            <th>Capacity</th>
                            <th>Colour</th>
                            <th>Supplier Condition</th>
                            <th>Recomm Condition</th>
                            <th>Supplier Grade</th>
                            <th>Recomm Grade</th>
                            <th>Network</th>
                            <th>Status</th>
                            <th>Shown To</th>
                            <th>Vat Type</th>
                            <th>Purchase date</th>
                            <th>Device Purchase Price</th>
                            <th>Total Purchase price</th>
                            <th>Sale price</th>
                            <th>Sale Price Ex Vat</th>
                            <th>Marginal Vat</th>


                            <th class="text-center"><i class="fa fa-globe"></i></th>
                        </tr>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($items_to_sell as $item)

                            <tr>
                                <td><a href="{{ route('stock.single', ['id' => $item->id]) }}">{{ $item->our_ref }}</a>
                                </td>
                                <td>{{ $item->third_party_ref }}</td>
                                <td>{{ str_replace( array('@rt'), 'GB', $item->name)   }}  </td>
                                <td>{{ $item->capacity_formatted }}</td>
                                <td>{{ $item->colour }}</td>
                                <td>{{$item->original_condition}}</td>
                                <td>{{ $item->condition }}</td>
                                <td>{{$item->original_grade}}</td>
                                <td>{{ $item->grade }}</td>
                                <td>{{ $item->network }}</td>
                                <td>{{ $item->status }}</td>
                                <td>{{ $item->shown_to }}</td>
                                <td>{{$item->vat_type}}</td>
                                <td>{{ $item->purchase_date ? $item->purchase_date->format('Y-m-d') : '' }}</td>
                                <td>{{money_format(config('app.money_format'),$item->purchase_price)}}</td>

                                <td>{{ money_format(config('app.money_format'),$item->total_cost_with_repair)  }}</td>
                                <td>{{money_format(config('app.money_format'),$item->sale_price)  }}</td>

                                <td>
                                    @if($item->vat_type===Stock::VAT_TYPE_STD)
                                        {{money_format(config('app.money_format'),$item->total_price_ex_vat)  }}
                                    @else
                                        N/A
                                    @endif
                                </td>

                                <td>{{ money_format(config('app.money_format'),$item->marg_vat) }}</td>
                                <td><img src="{{ $item->purchase_country_flag }}" alt=""></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    <div id="stock-pagination-wrapper">{!! $items_to_sell->appends(Request::all())->render() !!}</div>
                </div>
            </div>

            <div class="panel panel-default">
                <div data-toggle="collapse" data-target="#items-in-repair" class="panel-heading">Items In Repair <span
                            class="badge">{{ count($items_in_repair) }}</span></div>
                <div class="panel-body collapse" id="items-in-repair">
                    <table class="table table-condensed table-small">
                        <thead>
                        <tr>
                            <th>Ref</th>
                            <th>3rd-party ref</th>
                            <th>Name</th>
                            <th>Capacity</th>
                            <th>Colour</th>
                            <th>Supplier Condition</th>
                            <th>Recomm Condition</th>
                            <th>Supplier Grade</th>
                            <th>Recomm Grade</th>
                            <th>Network</th>
                            <th>Status</th>
                            <th>Shown To</th>
                            <th>Vat Type</th>
                            <th>Purchase date</th>
                            <th>Device Purchase Price</th>
                            <th>Total Purchase price</th>
                            <th>Sale price</th>
                            <th>Sale Price Ex Vat</th>
                            <th>Marginal Vat</th>
                            <th class="text-center"><i class="fa fa-globe"></i></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($items_in_repair as $item)

                            <tr>
                                <td><a href="{{ route('stock.single', ['id' => $item->id]) }}">{{ $item->our_ref }}</a>
                                </td>
                                <td>{{ $item->third_party_ref }}</td>
                                <td>{{ str_replace( array('@rt'), 'GB', $item->name)   }}  </td>
                                <td>{{ $item->capacity_formatted }}</td>
                                <td>{{ $item->colour }}</td>
                                <td>{{$item->original_condition}}</td>
                                <td>{{ $item->condition }}</td>
                                <td>{{$item->original_grade}}</td>
                                <td>{{ $item->grade }}</td>
                                <td>{{ $item->network }}</td>
                                <td>{{ $item->status }}</td>
                                <td>{{ $item->shown_to }}</td>
                                <td>{{$item->vat_type}}</td>

                                <td>{{ $item->purchase_date ? $item->purchase_date->format('Y-m-d') : '' }}</td>
                                <td>{{money_format(config('app.money_format'),$item->purchase_price)}}</td>


                                <td>{{ money_format(config('app.money_format'),$item->total_cost_with_repair)  }}</td>
                                <td>{{money_format(config('app.money_format'),$item->sale_price)  }}</td>

                                <td>
                                    @if($item->vat_type===Stock::VAT_TYPE_STD)
                                        {{money_format(config('app.money_format'),$item->total_price_ex_vat)  }}
                                    @else
                                        N/A
                                    @endif
                                </td>

                                <td>{{ money_format(config('app.money_format'),$item->marg_vat) }}</td>
                                <td><img src="{{ $item->purchase_country_flag }}" alt=""></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    <div id="stock-pagination-wrapper">{!! $items_in_repair->appends(Request::all())->render() !!}</div>
                </div>
            </div>

            <div class="panel panel-default">
                <div data-toggle="collapse" data-target="#items-returned" class="panel-heading">Items Returned <span
                            class="badge">{{ count($items_returned) }}</span></div>
                <div class="panel-body collapse" id="items-returned">
                    <table class="table table-condensed table-small">
                        <thead>
                        <tr>
                            <th>Ref</th>
                            <th>3rd-party ref</th>
                            <th>Name</th>
                            <th>Capacity</th>
                            <th>Colour</th>
                            <th>Supplier Condition</th>
                            <th>Recomm Condition</th>
                            <th>Supplier Grade</th>
                            <th>Recomm Grade</th>
                            <th>Network</th>
                            <th>Status</th>
                            <th>Shown To</th>
                            <th>Vat Type</th>
                            <th>Purchase date</th>
                            <th>Device Purchase Price</th>
                            <th>Total Purchase price</th>
                            <th>Sale price</th>
                            <th>Sale Price Ex Vat</th>
                            <th>Marginal Vat</th>
                            <th class="text-center"><i class="fa fa-globe"></i></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($items_returned as $item)
                            <tr>
                                <td><a href="{{ route('stock.single', ['id' => $item->id]) }}">{{ $item->our_ref }}</a>
                                </td>
                                <td>{{ $item->third_party_ref }}</td>
                                <td>{{ str_replace( array('@rt'), 'GB', $item->name)   }}  </td>
                                <td>{{ $item->capacity_formatted }}</td>
                                <td>{{ $item->colour }}</td>
                                <td>{{$item->original_condition}}</td>
                                <td>{{ $item->condition }}</td>
                                <td>{{$item->original_grade}}</td>
                                <td>{{ $item->grade }}</td>
                                <td>{{ $item->network }}</td>
                                <td>{{ $item->status }}</td>
                                <td>{{ $item->shown_to }}</td>
                                <td>{{$item->vat_type}}</td>


                                <td>{{ $item->purchase_date ? $item->purchase_date->format('Y-m-d') : '' }}</td>
                                <td>{{money_format(config('app.money_format'),$item->purchase_price)}}</td>

                                <td>{{ money_format(config('app.money_format'),$item->total_cost_with_repair)  }}</td>
                                <td>{{money_format(config('app.money_format'),$item->sale_price)  }}</td>

                                <td>
                                    @if($item->vat_type===Stock::VAT_TYPE_STD)
                                        {{money_format(config('app.money_format'),$item->total_price_ex_vat)  }}
                                    @else
                                        N/A
                                    @endif
                                </td>

                                <td>{{ money_format(config('app.money_format'),$item->marg_vat) }}</td>
                                <td><img src="{{ $item->purchase_country_flag }}" alt=""></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>

                    <div id="stock-pagination-wrapper">{!! $items_returned->appends(Request::all())->render() !!}</div>
                </div>
            </div>
        @endif
    </div>

@endsection