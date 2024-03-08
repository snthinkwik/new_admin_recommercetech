<?php

use App\Stock;
use App\Invoice;
use App\Mobicode\GsxCheck;
use App\Support\ReportParser;

$networks = Stock::getAdminUnlockableNetworks();
?>
<div class="form-group">
    <a class="btn btn-default btn-sm" href="{{ route('sales.export', ['id' => $sale->id]) }}"><i class="fa fa-download"> Export XLS</i></a>
</div>
{!! BsForm::open(['method' => 'post', 'route' => 'sales.check-all-networks']) !!}
{!! BsForm::hidden('id', $sale->id) !!}
{!! BsForm::groupSubmit('Check All Networks') !!}
{!! BsForm::close() !!}

{{--{!! BsForm::open(['method' => 'post', 'route' => 'sales.bulk-update-sale-price', 'class' => 'form-group form-inline']) !!}--}}
{{--{!! BsForm::hidden('id', $sale->id) !!}--}}
{{--<div class="form-group">--}}
{{--    <div class="input-group">--}}
{{--        <span class="input-group-addon">Bulk Update Sales Price</span>--}}
{{--        <span class="input-group-addon">&pound;</span>--}}
{{--        {!! BsForm::number('sale_price', null, ['min' => 0, 'step' => 0.01]) !!}--}}
{{--        <span class="input-group-btn">--}}
{{--            {!! BsForm::submit('Save') !!}--}}
{{--        </span>--}}
{{--    </div>--}}
{{--</div>--}}
{{--{!! BsForm::close() !!}--}}

{!! BsForm::open(['method' => 'post', 'route' => 'sales.change-multiple-item-sale-price']) !!}
<div class="d-flex ml-2">
    <a id="sales-unlock-button" href="javascript:" class="btn btn-sm btn-default">Unlock selected items</a>
    @if($sale->other_recycler || $sale->invoice_status == Invoice::STATUS_OPEN)
        @if($sale->invoice_status == Invoice::STATUS_OPEN)
            <a class="btn btn-default btn-sm toggle-update-btn ml-2" href="javascript:;"><i class="fa fa-pencil">
                    <span class="pl-2">Edit multiple sales price</span>
                </i></a>
            <span class="input-group-btn ml-2">
        {!! BsForm::button('Update Price',
        ['type' => 'submit',
        'class' => 'btn btn-sm btn-success confirmed d-none show-price-input',
        'data-confirm' => 'Are you sure you want to update item sales price?'
        ]) !!}
    </span>

        @endif
    @endif
</div>


<table class="table table-striped table-condensed">
    <thead>
    <tr>
        <th></th>
        <th>Ref</th>
        <th>3rd-party ref</th>
        <th>P/S Model</th>
        <th>Device Name</th>
        <th>Capacity</th>
        <th>Network</th>
        <th>Touch/Face ID Working?</th>
        <th>Cracked Back</th>
        <th>Grade</th>
        <th>IMEI</th>
        <th>Serial</th>
        <th>Total Purchase Price</th>
        {{--<th>Sales Price ex VAT</th>--}}
        <th>Vat Type</th>
        {{--<th>Total Purchase Price</th>--}}
        <th>Sales Price</th>
        <th>Sell Price Ex Vat</th>

        <th>Profit</th>
        <th>Profit %</th>
        <th>Marg VAT</th>
        <th>True Profit</th>
        <th>True Profit%</th>
        <th>Est Net Profit</th>
        <th>Est Net Profit%</th>
        @if($sale->other_recycler)
            <th>Remove</th>
            <th></th>
        @elseif($sale->invoice_status == Invoice::STATUS_OPEN)
            <th><i class="text-danger fa fa-remove"></i></th>
        @endif

        <th>Network Checks</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($sale->stock as $item)
        <tr>
            <td>
                <form>
                    @if ($item->imei && !$item->unlock && in_array($item->network, $networks))
                        {!! BsForm::checkbox('ids_to_unlock[' . $item->id . ']', 0, null, [
                        'data-toggle' => 'tooltip',
                        'title' => 'Mark to unlock',
                        'data-placement' => 'top'
                        ]) !!}
                    @else
                        <input disabled
                               type="checkbox"
                               @if (!$item->imei)
                               title="No IMEI."
                               @elseif ($item->network === 'Unlocked')
                               title="Already unlocked"
                               @elseif ($item->unlock)
                               title="Unlock already in progress"
                               @elseif (!in_array($item->network, $networks))
                               title="This network can't be unlocked."
                               @endif
                               data-toggle="tooltip"
                        >
                    @endif
                </form>
            </td>
            <td><a href="{{ route('stock.single', ['id' => $item->id]) }}">{{ $item->our_ref }}</a></td>
            <td>{{ $item->third_party_ref }}</td>
            <td>@if($item->ps_model)
                    Yes
                @else
                    No
                @endif
            </td>
            <td>{{str_replace( array('@rt'), 'GB', $item->name)}}</td>
            <td>{{ $item->capacity_formatted }}</td>
            <td>{{ $item->network }}</td>
            <td>{{$item->touch_id_working}}</td>
            <td>{{$item->cracked_back}}</td>
            <td>{{ $item->grade }}</td>
            <td>{{ $item->imei }}</td>
            <td>{{ $item->serial }}</td>
            {{--<td>{{ money_format(config('app.money_format'),  $item->total_price_ex_vat)   }}</td>--}}
            <td>{{  money_format($item->total_cost_with_repair)  }}</td>

            <td>{{ $item->vat_type }}</td>

            {{--<td>{{  money_format(config('app.money_format'),  $item->total_cost_with_repair)  }}</td>--}}
            <td width="100">
                {{ $item->sale_price_formatted }}
{{--                @if($sale->other_recycler || $sale->invoice_status == Invoice::STATUS_OPEN)--}}
{{--                    <a data-toggle="collapse" data-target="#price-{{ $item->id }}"><i class="fa fa-pencil edit"></i></a>--}}
{{--                    @if($sale->invoice_status == Invoice::STATUS_OPEN)--}}
{{--                        {!! BsForm::open(['method' => 'post', 'route' => 'sales.change-item-sale-price', 'class' => 'collapse collapsetoggle edit', 'id' => 'price-'.$item->id]) !!}--}}
{{--                        {!! BsForm::hidden('sale_id', $sale->id) !!}--}}
{{--                        {!! BsForm::hidden('stock_id', $item->id) !!}--}}
{{--                        <div class="form-group">--}}
{{--                            <div class="input-group">--}}
{{--                                {!! Form::number('sale_price', null, ['required' => 'required','step' => 0.01,'min'=>'1','style'=>"width:100px",'class'=>"salePrice"]) !!}--}}

{{--                                <span class="input-group-btn">--}}
{{--                            {!! BsForm::button("<i class='fa fa-check'></i>",--}}
{{--                            ['type' => 'submit',--}}
{{--                            'class' => 'btn btn-sm btn-success confirmed py3px add',--}}
{{--                            'data-confirm' => 'Are you sure you want to update item sales price?'--}}
{{--                            ]) !!}--}}
{{--                        </span>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                        {!! BsForm::close() !!}--}}
{{--                        {!! BsForm::hidden('sale_id', $sale->id) !!}--}}
{{--                        {!! BsForm::hidden('stock_id[]', $item->id) !!}--}}

{{--                        <div class="form-group">--}}
{{--                            <div class="input-group">--}}
{{--                                <input type="number" name="sale_price[]" step= "0.01" min='1' class="d-none show-price-input" style="width: 100px;">--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    @endif--}}
{{--                @endif--}}
            </td>

            @if($item->vat_type === "Standard")
                <td @if($item->total_price_ex_vat<0 ) class="text-danger" @endif>{{money_format($item->sale_price/1.2)}}</td>
                @else
                <td>N/A</td>
            @endif
            <td @if($item->profit < 0) class="text-danger" @endif>
                {{ money_format($item->profit)  }}
            </td>
            <?php
            if($item->vat_type === "Standard"){
                $profit=$item->total_price_ex_vat ? ($item->profit/$item->total_price_ex_vat)*100:0 ;
            }else{
                $profit=$item->sale_price?($item->profit/$item->sale_price) * 100:0 ;
            }

            ?>
            <td @if($profit < 0) class="text-danger" @endif>@if($item->vat_type === "Standard")
                    {{

                      $item->total_price_ex_vat ? number_format($item->profit/$item->total_price_ex_vat * 100,2)."%":0
                    }}
                @else
                    {{
                       $item->sale_price ? number_format($item->profit/$item->sale_price * 100,2)."%":0
                    }}
                @endif
            </td>

            @if($item->vat_type==="Margin")
                <td @if($item->marg_vat < 0) class="text-danger" @endif>

                    {{ money_format($item->marg_vat)}}

                </td>
                @else
                <td>-</td>
            @endif
            <td @if($item->true_profit < 0) class="text-danger" @endif >
            {{money_format($item->true_profit) }}
            </td>

            <?php
            if($item->vat_type === "Standard"){
                $trueProfit=$item->total_price_ex_vat ? ($item->true_profit/$item->total_price_ex_vat)*100:0 ;
            }else{
                $trueProfit=$item->sale_price? ($item->true_profit/$item->sale_price) * 100:0 ;
            }

            ?>

            <td @if($trueProfit<0 ) class="text-danger" @endif>
                @if($item->vat_type === "Standard")
                    {{
                      $item->total_price_ex_vat ? number_format($item->true_profit/$item->total_price_ex_vat * 100,2)."%":0
                    }}
                @else
                    {{
                      $item->sale_price ? number_format($item->true_profit/$item->sale_price * 100,2)."%":0
                    }}
                @endif

            </td>

            <?php
            if(count($sale->stock()->get())>1){

                $rt=($sale->platform_fee + $sale->shipping_cost) /count($sale->stock()->get());

                $estProfit=$item->true_profit - $rt ;


            }else{
                $estProfit=  $item->true_profit - $sale->platform_fee - $sale->shipping_cost;

            }

            ?>
            <td @if($estProfit<0) class="text-danger" @endif>
                {{ money_format($estProfit)}}
            </td>
            <?php
            if($item->vat_type===Stock::VAT_TYPE_STD){
            $estNetProfitPre=$item->total_price_ex_vat>0 ?  number_format(($estProfit/$item->total_price_ex_vat)*100,2):'';
            }
            else{
                $estNetProfitPre= $item->sale_price>0 ? number_format(($estProfit/$item->sale_price)*100,2):'';
            }


            ?>
            <td @if($estNetProfitPre<0) class="text-danger" @endif>
                {{ $estNetProfitPre.'%'}}

            </td>
            @if($sale->other_recycler)
                <td>
                    {!! BsForm::open(['route' => 'sales.other-remove-item']) !!}
                    {!! BsForm::hidden('sale_id', $sale->id) !!}
                    {!! BsForm::hidden('stock_id', $item->id) !!}
                    {!! BsForm::submit("Remove",
                    ['class' => 'btn btn-xs btn-default confirmed mb10',
                    'data-toggle' => 'tooltip', 'title' => "Remove from order", 'data-placement'=>'right',
                    'data-confirm' => "Are you sure you want to remove this item from the order?"])
                    !!}
                    {!! BsForm::close() !!}
                </td>
                <td>
                    <div class="collapse" id="price-{{ $item->id }}">
                        {!! BsForm::open(['method' => 'post', 'route' => 'sales.other-change-price']) !!}
                        {!! Form::hidden('stock_id', $item->id) !!}
                        {!! Form::hidden('sale_id', $sale->id) !!}
                        {!! Form::number('sale_price', null, ['placeholder' => 'Sales Price']) !!}
                        {!! BsForm::submit('Update',
                        ['class' => 'btn btn-sm btn-default confirmed',
                        'data-toggle' => 'tooltip', 'title' => "Update Sales Price", 'data-placement'=>'right',
                        'data-confirm' => "Are you sure you want to change this item sales price?"]) !!}
                        {!! BsForm::close() !!}
                    </div>
                </td>

                <td>Est Net profit</td>
            @elseif($sale->invoice_status == Invoice::STATUS_OPEN)
                <td>
                    {!! BsForm::open(['method' => 'post', 'route' => 'sales.remove-item-from-sale']) !!}
                    {!! BsForm::hidden('sale_id', $sale->id) !!}
                    {!! BsForm::hidden('stock_id', $item->id) !!}
                    {!! BsForm::button('<i class="fa fa-remove"></i>',
                    ['type' => 'submit',
                    'class' => 'btn btn-danger btn-xs confirmed',
                    'data-confirm' => 'Are you sure you want to remove this item from sale?'
                    ]) !!}
                    {!! BsForm::close() !!}
                </td>
            @endif



            <td>
                @foreach($item->sale_network_checks as $check)
                    <span data-toggle="popover" data-placement="left" data-title="{{ $check->created_at->format('d/m/y H:i:s') }}" data-html="true" data-content="{{ $check->report }}">
                    @if($check->status == \App\Models\Mobicode\GsxCheck::STATUS_DONE)
                            {{ ReportParser::getNetwork($check->report) ? : "Unknown" }} <i class="fa fa-info-circle"></i>
                        @else
                            {{ ucfirst($check->status) }}
                        @endif
                </span>
                @endforeach
            </td>
        </tr>
    @endforeach
    </tbody>
</table>

{!! BsForm::close() !!}
@section('scripts')
    <script>
        $(document).ready(function () {
            $('.toggle-update-btn').on("click", function () {
                $(".collapsetoggle").removeClass('in');
                $(".edit").toggleClass('d-none');
                $('.show-price-input').toggleClass('d-none');
            });
        });
    </script>
@endsection
