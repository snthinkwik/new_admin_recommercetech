<?php
use App\Models\Stock;
use Illuminate\Support\Facades\Request;
use Carbon\Carbon;
$query = array_filter(Request::query());
$emptySearch = !$query || count($query) === 1 && isset($query['status']) && $query['status'] === Stock::STATUS_IN_STOCK;
$hasExtraPriceData = false;
$atLeastOneInBasket = false;
//Colour::orderBy('pr_colour')->pluck('pr_colour', 'pr_colour')->toArray()

$basketIds = array_flip(Auth::user()->basket->pluck('id')->toArray());
foreach ($stock as $item) {
	if ($atLeastOneInBasket || isset($basketIds[$item->id])) {
		$atLeastOneInBasket = true;
	}
	if ($item->purchase_foreign_price) {
		$hasExtraPriceData = true;
		break;
	}
}
?>
@if (!count($stock))
	<div class="alert alert-danger">
		@if ($emptySearch)
			Currently out of stock
		@else
			Currently out of stock
		@endif
	</div>
@else

	{{--<form>--}}
	@if(Auth::user()->type == 'admin' && in_array(Auth::user()->admin_type, ['admin', 'manager']))
		<div>
			<a href="javascript:" id="stock-toggle-all" class="btn btn-default btn-xs pr25" {!! $atLeastOneInBasket ? 'data-is-selected="1"' : '' !!}>
				<img src="{{ asset('/img/ajax-loader.gif') }}">
				<span>{{ $atLeastOneInBasket ? 'Unselect all' : 'Select all' }}</span>
			</a>
		</div>
	@endif
	<p class="small"><h5><b>Item Count:</b> {{ $stock->total() }}</h5></p>

		<table class="table small stock table-h-sticky">
			<thead>
			<tr id="item-sort">
				<th></th>
				<th name="trg_ref">Ref</th>
				<th>Supplier Id</th>

				@if (Auth::user()->type !== 'user')
					<th name="third_party_ref">3rd-party ref</th>
				@endif
				<th>P/S Model</th>
				<th name="make">Make</th>
				<th name="product_type">Category</th>
				<th name="name">Name</th>
				<th name="capacity">Capacity</th>
				<th name="colour">Colour</th>
				<th  name="original_condition" style="text-align: center">Supplier Condition</th>
				@if (Auth::user()->canRead('stock.condition'))
					<th name="condition">Recomm Condition</th>
				@endif
				<th onabort="original_grade">Supplier Grade</th>
				<th name="grade">Recomm Grade</th>
				<th name="">Test Status</th>
				<th name="touch_id_working">Touch/Face ID Working?</th>
				<th name="cracked_back">Cracked Back</th>
				<th name="network">Network</th>
				<th name="imei">IMEI / Serial</th>
				<th name="status">Status</th>
				{{--<th name="vat_type">Vat Type</th>--}}
				@if(Auth::user()->type == 'admin')
					<th name="purchase_date">Purchase date</th>
				@endif
				@if (Auth::user()->type == 'admin' && in_array(Auth::user()->admin_type, ['admin', 'manager']))
					<th name="purchase_price">Purchase price</th>

				@endif
				<th>Unlock Cost</th>
				<th>Repair Cost</th>
				<th>Total Purchase Price</th>
				<th name="vat_type">Vat Type</th>
				@if (Auth::user()->type == 'admin' && in_array(Auth::user()->admin_type, ['admin', 'manager']))
					{{--<th name="purchase_price">Purchase price</th>--}}
					@if ($hasExtraPriceData) <th></th> @endif

					<th name="sale_price">Sales price</th>
				@endif
				<th name="sales_price_ex_vat">Sales Price ex VAT </th>
				<th>Profit</th>
				@if (Auth::user()->type == 'admin' && in_array(Auth::user()->admin_type, ['admin', 'manager']))
					<th name="margin">% Margin</th>
				@endif
				<th>Marg VAT</th>
				<th>True Profit</th>
				<th>True Profit %</th>
				<th>Est Net Profit</th>
				<th>Est Net Profit %</th>

				<th name="created_at">No Days Old</th>
				@if (Auth::user()->type == 'admin' && in_array(Auth::user()->admin_type, ['admin', 'manager']))
					<th name="supplier_id"><i class="fa fa-truck" data-toggle="tooltip" title="Supplier"></i></th>
					<th name="purchase_country"><i class="fa fa-globe"></i></th>
				@endif
			</tr>
			</thead>
			<tbody style="text-align: center">

			@foreach ($stock as $item)


				<?php $inBasket = count($basket)? $basket->whereLoose('id', $item->id)->count():0; ?>
				<tr
					@if(Auth::user()->type === 'admin' && in_array($item->grade, [Stock::GRADE_LOCKED_LOST, Stock::GRADE_LOCKED_CLEAN])) class="bg-red"
					@elseif(Auth::user()->type === 'admin' && $item->shown_to == Stock::SHOWN_TO_EBAY_AND_SHOP) class="info"
					@elseif(Auth::user()->type === 'admin' && $item->shown_to === Stock::SHOWN_TO_EBAY) class="ebay"
					@elseif(Auth::user()->type === 'admin' && $item->inWarranty) class="orchid"
					@elseif(Auth::user()->type === 'admin' && $item->shown_to === Stock::SHOWN_TO_ALL) class="success"
					@endif
				>
					<td width="10%">
						@if ($item->sale_id)
							<div class="text-muted">sold</div>
{{--						@elseif ($item->status === Stock::STATUS_BATCH)--}}
{{--							<div class="text-muted">batch #{{ $item->batch_id }}</div>--}}
						@elseif (Auth::user() && in_array($item->status, Auth::user()->allowed_statuses_buying))
							@if(Auth::user()->type !== 'admin' && !in_array($item->shown_to, [Stock::SHOWN_TO_ALL, Stock::SHOWN_TO_EBAY_AND_SHOP, Stock::SHOWN_TO_EBAY]))
								<div class="text-muted">In Testing</div>
							@elseif(Auth::user()->type !== 'admin' && $item->status === Stock::STATUS_INBOUND)
								<div class="text-muted">Inbound</div>
							@else
								{!! Form::checkbox('stock_ids[]', $item->id, $inBasket) !!}
							@endif
						@endif
					</td>
					<td><a class="sku-link" href="{{ route('stock.single', ['id' => $item->id]) }}">{{ $item->our_ref }}</a></td>
					<td>
                        @if(!is_null($item->supplier_id))
                        <a href="{{route('suppliers.single',['id'=>$item->supplier_id])}}"> {{$item->supplier_id}}</a>
                        @else
                            -
                        @endif
                    </td>
					<td>{{ $item->third_party_ref }}</td>
					<td>
						@if($item->ps_model)
						Yes
						@else
					    No
						@endif
					</td>
					<td>{{ $item->make }}</td>
					<td>{{ $item->product_type }}</td>
					<td>


						{{ str_replace( array('@rt'), 'GB', $item->name)   }}</td>
					<td>{{ $item->capacity_formatted }}</td>
					<td>{{ $item->colour }}</td>
					<td>


						@if(!is_null($item->original_condition))
						{{$item->original_condition}}<br>
						{{'(RCM '. getSupplierMappingGrade($item->supplier_id,$item->original_condition).')'}}
						@endif

					</td>
					@if (Auth::user()->canRead('stock.condition'))
						<td style="width: 2%; !important;">
							@if (!$item->condition && Auth::user() && Auth::user()->type === 'user')
								A to C
							@else
								{{ $item->condition }}
							@endif
						</td>
					@endif
					<td>{{$item->original_grade}}</td>
					<td>
						@if(Auth::user()->type !== 'admin' && !in_array($item->shown_to, [Stock::SHOWN_TO_ALL, Stock::SHOWN_TO_EBAY_AND_SHOP, Stock::SHOWN_TO_EBAY]))
							Unknown
						@else
							{{ $item->grade }}
						@endif
					</td>
					<td>
						<?php
						$erasureStatus='';
						?>
						@if(!is_null($item->phone_check))


							@if(strpos($item->phone_check->report_render, 'Erasure Status') !== false)

								<?php
								$whatIWant = substr($item->phone_check->report_render, strpos($item->phone_check->report_render, "Erasure Status") + 15);


								$output = substr($whatIWant, 0, strpos($whatIWant, 'Working'));

								if(strip_tags($output)===" Yes"){
									$erasureStatus= strip_tags($output);
								}

								?>

							@endif
						@endif
						@if($item->test_status===Stock::TEST_STATUS_COMPLETE) <span class="text text-success">  {{$item->test_status}}</span>
							<br>
							{{$erasureStatus}}
						@elseif($item->test_status===Stock::TEST_STATUS_PENDING) <span class="text text-warning">  {{$item->test_status}}
							</span>
							<br>
							{{$erasureStatus}}
						@elseif($item->test_status===Stock::TEST_STATUS_UNTESTED) <span class="text text-danger">  {{$item->test_status}}
							</span>
							<br>
							{{$erasureStatus}}
						@else  - @endif
					</td>

					<td>{{$item->touch_id_working}}</td>
					<td>{{$item->cracked_back}}</td>
					<td>
						{{ $item->network }}
						@if (Auth::user() && Auth::user()->type !== 'user' && $item->network === 'Unknown' && $item->imei_report)
							<small class="text-muted">pending</small>
						@endif
						@if (Auth::user() && Auth::user()->type === 'user')
							@if($item->vodafone_unable_to_unlock)
								<span
										class="label label-danger big ib"
										data-toggle="popover"
										data-trigger="hover"
										data-content="this Vodafone device cannot be unlocked by us."
								>
									<i class="fa fa-lock"></i>
								</span>
							@elseif ($item->free_unlock_eligible)
								<span
									class="label label-info big ib unlock"
									data-toggle="popover"
									title="Free unlock"
									data-trigger="hover"
									data-content="This device is eligible for a free unlock. Once payment has been received we will submit the IMEI for an unlock which can take between 24 to 48 hours."
								>
									<i class="fa fa-unlock-alt"></i>
								</span>
								<small>Free unlock</small>
							@elseif ($item->network === 'Unknown')
								<span
									class="label label-default big ib"
									data-toggle="popover"
									title="Unknown network"
									data-trigger="hover"
									data-content="We don't currently know the network of this device but we guarantee it to be a UK network if not already unlocked. For Apple products our system will automatically check the network and update within 24 hours."
								>
									<i class="fa fa-question-circle"></i>
								</span>
							@endif
						@endif
					</td>
					<td>
						{{$item->imei!=="" ?$item->imei:$item->serial}}
					</td>
					<td class="status">
						@if(Auth::user()->type !== 'admin' && !in_array($item->shown_to, [Stock::SHOWN_TO_ALL, Stock::SHOWN_TO_EBAY_AND_SHOP, Stock::SHOWN_TO_EBAY]))
							In Testing
						@else
							{{ $item->status }}
							@if(Auth::user()->type === 'admin' && in_array($item->shown_to, [Stock::SHOWN_TO_EBAY, Stock::SHOWN_TO_EBAY_AND_SHOP]))
								@if($item->listed == "yes")
									<span class="text-success" data-toggle="tooltip" title="This item has been listed on Channel Grabber"><i class="fa fa-check fa-lg"></i></span>
								@elseif($item->listed == "error")
									<span class="text-danger" data-toggle="tooltip" title="The SKU for this item is invalid and could not be updated to Channel Grabber"><i class="fa fa-exclamation-triangle fa-lg"></i></span>
								@endif
							@endif
							@if ($item->status === Stock::STATUS_INBOUND)
								<span
									class="label label-default big ib"
									data-toggle="popover"
									title="Inbound stock"
									data-trigger="hover"
									data-content="These devices are currently inbound from RCT customers and expected to arrive in the next 48 hours. Once the device has been tested and passed QC it will be shipped to you separately from the rest of your order"
									data-placement="left"
								>
									<i class="fa fa-question-circle"></i>
								</span>
								@if(Auth::user()->type == 'admin')
									{!! Form::open(['route' => 'stock.item-receive', 'method' => 'post', 'id' => "inbound-item-receive-$item->id"]) !!}
									{!! BsForm::hidden('stock_id', $item->id) !!}
									{!! BsForm::button('<i class="fa fa-check"></i> Receive',
										['type' => 'submit',
										'class' => 'btn btn-success btn-block btn-xs confirmed',
										'data-toggle' => 'tooltip', 'title' => "Receive Item", 'data-placement'=>'left',
										'data-confirm' => "Are you sure you want to receive this item?"])
									!!}
									{!! Form::close() !!}

									{!! Form::open(['route' => 'stock.item-delete', 'method' => 'post', 'id' => "inbound-item-delete-$item->id"]) !!}
									{!! BsForm::hidden('stock_id', $item->id) !!}
									{!! BsForm::button('<i class="fa fa-trash"></i> Delete',
										['type' => 'submit',
										'class' => 'btn btn-danger btn-xs btn-block confirmed',
										'data-toggle' => 'tooltip', 'title' => "Delete Item", 'data-placement'=>'right',
										'data-confirm' => "Are you sure you want to delete this item?"])
									!!}
									{!! Form::close() !!}
								@endif
							@endif
						@endif
					</td>
					{{--<td>{{ $item->vat_type }}</td>--}}
					@if (Auth::user()->type !== 'user')
{{--						<td>{{ $item->purchase_date ? $item->purchase_date->format('Y-m-d') : '' }}</td>--}}
                        <td>{{$item->purchase_date }}</td>
					@endif
					@if (Auth::user()->type == 'admin' && in_array(Auth::user()->admin_type, ['admin', 'manager']))
					<td>
{{--                        {{ money_format(config('app.money_format'), $item->purchase_price) }}--}}
                        {{$item->purchase_price}}
                    </td>
					@endif
					<td>
{{--                        {{money_format(config('app.money_format'), $item->unlock_cost)}}--}}
                    {{$item->unlock_cost}}
                    </td>
					<td>
{{--                        {{money_format(config('app.money_format'), $item['total_repair_cost'] )}}--}}

                        {{$item['total_repair_cost']}}

                    </td>
					<td>
{{--                        {{ money_format(config('app.money_format'), $item->total_cost_with_repair) }}--}}

                        {{$item->total_cost_with_repair}}
                    </td>
					<td>{{ $item->vat_type }}</td>
					@if (Auth::user()->type == 'admin' && in_array(Auth::user()->admin_type, ['admin', 'manager']))

						@if ($hasExtraPriceData)
							<td class="foreign-purchse-price">
								@if ($item->purchase_foreign_country)
									<img src="{{ asset('/img/stripe-flag-set/' . $item->purchase_foreign_country) . '.png' }}">
								@endif
								@if ($item->purchase_foreign_price) {{ $item->purchase_foreign_price_formatted }} @endif
							</td>
						@endif
						<td>{{ $item->sale_price_formatted }}</td>
					@endif

					<td @if($item->total_price_ex_vat < 0) class="text-danger" @endif>
					@if($item->total_price_ex_vat)
{{--                            {{money_format(config('app.money_format'), $item->total_price_ex_vat) }}--}}
                        {{$item->total_price_ex_vat}}

                        @else N/A @endif</td>

					<td @if($item->profit < 0) class="text-danger" @endif>
{{--					{{ money_format(config('app.money_format'), $item->profit) }}--}}
                        {{$item->profit}}
                    </td>

					<td @if($item->margin_formatted < 0) class="text-danger" @endif>{{ $item->margin_formatted }}</td>
					<td @if($item->marg_vat < 0) class="text-danger" @endif>
{{--						@if(!is_null($item->marg_vat)) {{money_format(config('app.money_format'), $item->marg_vat) }} @else N/A @endif--}}
                            @if(!is_null($item->marg_vat)) {{$item->marg_vat }} @else N/A @endif
					</td>
					<td @if($item->true_profit < 0) class="text-danger" @endif>
{{--                        {{money_format(config('app.money_format'), $item->true_profit) }}--}}
                        {{$item->true_profit}}

                    </td>


                    <?php
                    if($item->vat_type === "Standard"){
                        $trueProfit=$item->total_price_ex_vat ? ($item->true_profit/$item->total_price_ex_vat)*100:0 ;
                    }else{
                        $trueProfit=$item->sale_price ? ($item->true_profit/$item->sale_price) * 100:0 ;
                    }

                    ?>
					<td @if($trueProfit < 0) class="text-danger" @endif>@if($item->vat_type==="Standard")

						{{$item->total_price_ex_vat ? number_format($item->true_profit/$item->total_price_ex_vat*100,2)  ."%":''}}
					@else
							{{$item->sale_price ? number_format($item->true_profit/$item->sale_price*100,2) ."%":''}}
					@endif</td>


					<?php
					$estProfit=0;
					if($item->status === 'Sold'){
						if(isset($item->sale->platform_fee) && isset($item->sale->shipping_cost)  ){
							$estProfit=$item->true_profit - $item->sale->platform_fee ;
						}
					}else{
						$estProfit=$item->true_profit;
					}

					?>


                    @if($item->status === 'Sold')
								@if(isset($item->sale))
								@if(in_array($item->sale->platform,[Stock::PLATFROM_MOBILE_ADVANTAGE,Stock::PLATFROM_BACKMARCKET,Stock::PLATFROM_EBAY]))
											<td>
{{--                                                {{money_format(config('app.money_format'),$estProfit)}}--}}
                                                {{$estProfit}}
                                            </td>
								@else
									<td  @if($trueProfit < 0) class="text-danger" @endif >
{{--                                        {{money_format(config('app.money_format'),$item->true_profit)}}--}}
                                    {{$item->true_profit}}
                                    </td>
								@endif
									@endif
                    @else
                        <td @if($trueProfit < 0) class="text-danger" @endif>
{{--                            {{money_format(config('app.money_format'),$item->true_profit)}}--}}
                            {{$item->true_profit}}
                        </td>
                    @endif


						<?php
						$estProfitPre=0;
								if($item->vat_type===Stock::VAT_TYPE_STD){
									$amount=$item->total_price_ex_vat;
								}else{
									$amount=$item->sale_price;
								}
								$estProfitPre = $amount ? ($estProfit / $amount) * 100:0;
						?>


					<td>{{number_format($estProfitPre,2).'%'}}</td>


					<td>{{ $item->created_at->diffInDays(Carbon::now()) }}</td>
					@if (Auth::user()->type == 'admin' && in_array(Auth::user()->admin_type, ['admin', 'manager']))
						<td>@if($item->supplier_id) <a href="{{ route('suppliers') }}">#{{ $item->supplier_id }}</a> @endif</td>
						<td><img src="{{ $item->purchase_country_flag }}" alt=""></td>
					@endif
				</tr>
			@endforeach
			</tbody>
		</table>
	</form>
@endif
