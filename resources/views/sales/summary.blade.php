<?php
use App\Models\Sale;
use App\Models\Stock;
use App\Models\SellerFees;
use Illuminate\Support\Facades\Request;
$hasAvailabilityError = false;
if(Request::input('items')) {
    foreach (Request::input('items') as $id => $itemData) {
        if ($errors->has('items.' . $id . '.price') || $errors->has('items.' . $id . '.status')) {
            $hasAvailabilityError = true;
            break;
        }
    }
}
$invoicing = app('App\Contracts\Invoicing');
$networks = Stock::getAdminUnlockableNetworks();
$totalSalePrice = 0;
$totalPurchasePrice=0;
$totalProfit=0;
$totalTrueProfit=0;
$grossProfit = $stock->sum('gross_profit');
$grossProfitPercentage = 0;
$totalGrossProfit = $stock->sum('total_gross_profit');
$totalGrossProfitPercentage = 0;
$totalExVatPrice=0;
$profitPercentage=0;
$trueProfitPercentage=0;


foreach($stock as $item) {
    $totalPurchasePrice +=$item->total_cost_with_repair;
    $totalProfit +=$item->profit;
    $totalTrueProfit +=$item->true_profit;
    $totalExVatPrice +=$item->total_price_ex_vat;
    if (!empty($request->items[$item->id]['price'])) {
        $totalSalePrice += $request->items[$item->id]['price'];

    } else {
        $totalSalePrice += $item->sale_price;

    }
}

if($totalSalePrice>0){
    $grossProfitPercentage = $totalSalePrice ? number_format($grossProfit / $totalSalePrice * 100, 2):0;
    $totalGrossProfitPercentage =$totalSalePrice ? number_format($totalGrossProfit / $totalSalePrice *100, 2):0;
}



if($stock[0]['vat_type']==="Standard"){

    $profitPercentage=$totalExVatPrice >0 ? number_format($totalProfit/$totalExVatPrice * 100,2):0;
    $trueProfitPercentage=$totalExVatPrice>0 ? number_format($totalTrueProfit/$totalExVatPrice * 100,2):0;
}else{


    $profitPercentage=$totalSalePrice > 0 ?number_format($totalProfit/$totalSalePrice * 100,2):0;
    $trueProfitPercentage=$totalSalePrice  > 0 ? number_format($totalTrueProfit/$totalSalePrice * 100,2):0;
}

session()->forget('pre_data');

$SellerFess = SellerFees::groupBy('platform')->get();
$platformList=[];

foreach ($SellerFess as $key=>$platform){
    $platformList['']="Select PlatForm";
    $platformList[$platform['platform']]=$platform['platform'];
}


?>
@extends('app')

@section('title', 'Confirm ' . Auth::user()->texts['sales']['entity'])

@section('scripts')
	<script>
        $('#summary-select-all-to-unlock-button').click();
	</script>
@endsection
<style type="text/css">
	.tool-tip-disable{
		cursor: not-allowed;
	}
	.tool-tip-disable input[type="checkbox"][disabled], input[type="checkbox"].disabled{
		z-index: -1;
		position: relative;
		cursor: not-allowed;
	}
	.tool-tip {
		display: inline-block;
	}

	.tool-tip [disabled] {
		pointer-events: none;
	}
</style>
@section('content')

	<div class="container-fluid">
		@include('messages')

		@if (!empty($errorMessage))
			<div class="alert alert-danger" role="alert">
				<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <?= $errorMessage ?>
			</div>
		@endif

		<h3>{{ ucfirst(Auth::user()->texts['sales']['entity']) }} summary</h3>

		@if ($errors->has('buyers_ref'))
			<h1>Error</h1>
		@endif
		@if ($errors->has('order_amount'))
			<div class="alert alert-danger">
				Your order value is below the MOQ of {{ money_format(Sale::MINIMUM_ORDER_AMOUNT) }} -
				please add more items to check out
			</div>
		@endif
		@if ($hasAvailabilityError)
			<div class="alert alert-danger">
				It seems that some prices or availability of some items has changed since you started creating the order. You can try
				<a class="alert-link" href="{{ Illuminate\Support\Facades\Request::fullUrl() }}">refreshing this page</a> or
				going back to the <a class="alert-link" href="{{ route('stock') }}">stock page</a>.
			</div>
		@endif
		@if (count($stock) !== count($request->items) && empty($errorMessage))
			<p class="text-warning">Some of the items you requested are not shown because they're not available for sale.</p>
		@endif
		{!! Form::open(['method' => 'POST', 'route' => 'sales.save', 'id' => 'sale-summary-form', 'class' => 'mb15']) !!}
		@if(count($stock)>0)
			<h4>Items</h4>
			<p>Total Purchase Price: {{ money_format( $totalPurchasePrice) }}</p>
			<p>Total Sales Price: {{ money_format( $totalSalePrice) }}</p>
			<p>Total Sales Price Ex Vat: {{ money_format( $totalExVatPrice) }}</p>
			<p><span>Total Profit: {{ money_format( $totalProfit) }}</span>
				<span class="p45">Total Profit %:{{$profitPercentage."%"}}</span>

			</p>
			@if($stock[0]['vat_type']!=="Standard")
				<p>Total True Profit: {{ money_format($totalTrueProfit) }}
					<span class="p15">Total True Profit %:{{$trueProfitPercentage."%"}}</span>
				</p>
			@endif

			@if(Auth::user()->type == 'admin')
				<a id="summary-select-all-to-unlock-button" class="btn btn-default btn-sm">Select All to Unlock</a>
			@endif
			<table class="table table-striped">
				<table class="table">
					<thead>
					<tr>
						<th>RCT Ref</th>
						<th>Third Party</th>
						<th>IMEI</th>
						<th>Touch/Face ID Working?</th>
						<th>Cracked Back</th>
						<th>Name</th>
						<th>Capacity</th>
						<th>Colour</th>
						<th>Grade</th>
						<th>Network</th>
						@if (Auth::user()->type !== 'user')
							<th>Total Purchase price</th>
						@endif
						<th>Sell Price</th>
						<th>Sell Price Ex Vat</th>
						<th>Vat Type</th>
						<th>Profit</th>
						<th>Profit %</th>
						@if($stock[0]['vat_type']==="Margin")
							<th>VAT Margin</th>
						@endif
						<th>True Profit</th>
						<th> True Profit %</th>
						<th>Remove from basket</th>
						@if(Auth::user()->type === 'admin')
							<th>Unlock</th>
						@endif
					</tr>
					</thead>
					<tbody>
					@foreach ($stock as $item)
						<tr>
							<td>
								{!!
									Form::hidden(
										'items[' . $item->id . '][price]',
										isset($data['items'][$item->id]['price']) ? $data['items'][$item->id]['price'] : $item->sale_price,
										['class' => 'form-control', 'placeholder' => 'Price']
									)
								!!}
								<a href="{{ route('stock.single', ['id' => $item->id]) }}" target="_blank">{{ $item->our_ref }}</a>
							</td>
							<td>{{$item->third_party_ref}}</td>
							<td width="7%">{{$item->imei}}</td>
							<td width="1%">{{$item->touch_id_working}}</td>
							<td width="1%">{{$item->cracked_back}}</td>
							<td>{{ str_replace( array('@rt'), 'GB', $item->name)   }}</td>
							<td>{{ $item->capacity_formatted }}</td>
							<td>{{ $item->colour }}</td>
							<td>{{ $item->grade }}</td>
							<td>{{ $item->network }}</td>
							@if (Auth::user()->type !== 'user')
								<td width="5%">{{ money_format($item->total_cost_with_repair)  }}</td>
							@endif
							<td>
								@if (isset($data['items'][$item->id]['price']) && !empty($data['items'][$item->id]['price']))
									£{{ number_format($data['items'][$item->id]['price'], 2) }}
								@else
									{{ $item->sale_price_formatted }}
								@endif
							</td>

							<td width="4%" @if($item->total_price_ex_vat < 0) class="text-danger" @endif>@if($item->total_price_ex_vat){{ money_format($item->total_price_ex_vat)  }} @else - @endif</td>
							<td>{{ $item->vat_type }}</td>
							<td @if($item->profit < 0) class="text-danger" @endif>{{ money_format($item->profit)   }}</td>

                            <?php
                            if($item->vat_type === "Standard"){
                                $profit=$item->total_price_ex_vat ? ($item->profit/$item->total_price_ex_vat)*100:0 ;
                            }else{
                                $profit=$item->sale_price ? ($item->profit/$item->sale_price) * 100:0 ;
                            }

                            ?>

							<td @if($profit < 0) class="text-danger" @endif>
								@if($item->vat_type === "Standard")
									{{

									  $item->total_price_ex_vat ? number_format($item->profit/$item->total_price_ex_vat * 100,2)."%":0
									}}
								@else
									{{
									   $item->sale_price ? number_format($item->profit/$item->sale_price * 100,2)."%":0
									}}
								@endif

							</td>
							@if($stock[0]['vat_type']==="Margin")
								<td>{{money_format($item->marg_vat) }}</td>
							@endif
							<td @if($item->true_profit < 0) class="text-danger" @endif>{{  money_format($item->true_profit)  }}</td>

                            <?php
                            if($item->vat_type === "Standard"){
                                $trueProfit=$item->total_price_ex_vat ? ($item->true_profit/$item->total_price_ex_vat)*100:0 ;
                            }else{
                                $trueProfit=$item->sale_price ? ($item->true_profit/$item->sale_price) * 100:0 ;
                            }

                            ?>
							<td @if($trueProfit < 0) class="text-danger" @endif>
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
							<td>
								<a class="btn btn-warning btn-xs" href="{{ route('basket.delete-item', ['id' => $item->id]) }}">Remove from basket</a>
							</td>
							@if(Auth::user()->type == 'admin')
								<td>
									@if($item->imei && !$item->unlock && in_array($item->network, $networks) && !$item->vodafone_unable_to_unlock)
										{!! BsForm::checkbox('items[' . $item->id . '][unlock]', $item->id, null, [
											'data-toggle' => 'tooltip',
											'title' => 'Mark to Unlock',
											'data-placement' => 'right',
										]) !!}
									@else
										<span  class="tool-tip-disable" @if (!$item->imei)
										title="No IMEI."
											   @elseif ($item->network === 'Unlocked')
											   title="Already unlocked"
											   @elseif ($item->unlock)
											   title="Unlock already in progress"
											   @elseif (!in_array($item->network, $networks))
											   title="This network can't be unlocked."
											   @elseif($item->vodafone_unable_to_unlock)
											   title="this Vodafone device cannot be unlocked by us."
											   @endif
											   rel="tooltip"
											   data-toggle="tooltip">
										<input disabled="disabled"
											   type="checkbox" >
                                        </span>
									@endif
								</td>
							@endif
						</tr>
					@endforeach
					</tbody>
				</table>
				@endif

				@if(!is_null($parts)>0)
					<h4>Parts</h4>
					<table class="table table-striped table-hover">
						<thead>
						<tr>
							<th>Part</th>
							<th>Quantity</th>
							<th>Single Price</th>
							<th>Total Price</th>
						</tr>
						</thead>
						<tbody>
						@foreach($parts as $part)
							<tr>
								<td>{{ $part->part->long_name }}</td>
								<td>{{ $part->quantity }}</td>
								<td>{{ money_format($part->part->sale_price) }}</td>
								<td>{{ money_format($part->part_total_amount) }}</td>
							</tr>
						@endforeach
						</tbody>
					</table>
				@endif

				<div class="form-group">
					{!! BsForm::checkbox('return_policy', true, false, ['required' => 'required']) !!} I understand and agree to <a data-target="#return-policy" data-toggle="collapse">RCT's 14 day return policy</a>
				</div>

				<div class="panel panel-default collapse" id="return-policy">
					<div class="panel-body">
						<p>
							RCT do not open the phones it sells and we therefore make best endeavours to ensure they are minor faults. We do however expect that there are some issues such as IC issues or water damage that can only be seen once a phone repair has commenced. RCT therefore do expect a percentage of returns with the nature of the items we sell.
						</p>
						<p>
							RCT offer a hassle free exchange policy within the 14 days of the device being delivered and we issue replacements for these devices.
						</p>
						<p>
							For any returns the devices <b>MUST</b> be returned back within 14 days to qualify for a replacement, no exceptions will be made. We have a strict 14 days return period as this is what our suppliers give to us. For the avoidance of any confusion, the devices must physically be with RCT within the 14 day period.
						</p>
						<p>
							If you do not feel you can test items and return the rejects within 14 days then please do not place an order.
						</p>
					</div>
				</div>

				<div class="alert alert-success">
					Please be aware that you are placing a real order and once you click Create Order you are committing to buy this stock.
					The next screen you see will be Sage Pay to take payment.
					If you place the order and do not complete the sale your ability to purchase stock from RCT in future may be restricted.
				</div>
				@if (Auth::user()->type !== 'user')
					<div class="row">
						<div class="col-md-4">
							<div class="input-group">
								<span class="input-group-addon">PlatForm</span>
								{!! BsForm::select('platform', $platformList,'Recomm', ['class'=>'platform']) !!}
							</div>

							{!! Form::label('buyers_ref', 'Add buyers reference number') !!}
							{!! Form::text('buyers_ref', null, ['class' => 'form-control', 'placeholder' => 'Buyers ref']) !!}

							{!! BsForm::groupCheckbox('customer_is_collecting') !!}
							{{--<div class="form-group">--}}

							{{--</div>--}}
							<div class="input-group">
								<span class="input-group-addon">Customer ID</span>
								{!! Form::number('customer_id', null, ['placeholder' => 'Customer ID', 'min' => 1, 'step' => 1, 'id' => 'summary-customer-load-input', 'class' => 'form-control', 'required' => 'required']) !!}
								<span class="input-group-btn">{!! BsForm::button('Load', ['id' => 'summary-customer-load-button']) !!}</span>
							</div>

							<div class="form-group @hasError('customer_external_id')">
								{{--<label for="sale-customer">Customer</label>--}}
								{!! Form::hidden('customer_external_id', null) !!}
								{{--{!!
                                    Form::text(
                                        'customer_external_name',
                                        null,
                                        ['class' => 'form-control customer-field click-select-all', 'placeholder' => 'Search or select']
                                    )
                                !!}--}}
								<p class="text-muted small">
									If you can't find an existing {{ $invoicing->getSystemName() }} customer, please
									<a target="_blank" href="{{ route('admin.users') }}">add them</a> first.
								</p>
								@error('customer_external_id') @enderror
							</div>
						</div>
						<div class="col-md-8">
							<fieldset id="customer-fieldset" class="mt25"></fieldset>
							{!! Form::hidden('customer_modified', 0) !!}
						</div>
					</div>
		@endif

		{!! Form::submit('Create ' . Auth::user()->texts['sales']['entity'], ['class' => 'btn btn-success', 'id' => 'summary-form-submit-button']) !!}
		{!! Form::close() !!}
	</div>

@endsection

@section('pre-scripts')
	{{--<script>
		Data.sales.customers = {!! json_encode($customersForAutocomplete) !!};
	</script>--}}
@endsection

@section('nav-right')
	<div id="basket-wrapper" class="navbar-right pr0">
		@include('basket.navbar')
	</div>
@endsection
