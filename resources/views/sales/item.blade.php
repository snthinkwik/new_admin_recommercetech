<?php
use App\Models\Invoice;
use App\Models\Stock;



$totalExVat = 0;
$totalPurchasePrice=0;
$totalProfit=0;
$totalTrueProfit=0;
$totalSalePrice=0;
$totalVatMargin=0;
$totalPurchaseCost=0;
$purchasePrice=0;
$totalSalePriceDelivery=0;
$totalUnlockCost=0;
$totalPartCost=0;
$totalRepairCost=0;
$totalSplitProfit=0;
$estProfitSPModel=0;
$estProfitNonSPModel=0;
$recomeePer=[];
$totalItemsSoldPS=0;
$totalItemsSoldNonPS=0;


$vatTypeList=[];

$postCode='';
$totalNetProfit='';
$totalExVatPrice=0;
$psTotalSalePrice=0;
$psTotalVat=0;
$supplierPre='';
$ftProfit=0;
$ftTrueProfit=0;



if(count($sale->ebay_orders)>0){



	foreach ($sale->ebay_orders as $ebay){

		$postCode=$ebay->post_to_postcode;
		$platform=$ebay->platform;
		foreach ($ebay->EbayOrderItems as $item){

			$taxRate = 0;
			$totalCosts = 0;
			$vatType='';
			$itemsPrice=0;
			$purchasePriceStock=0;


			if( $item->quantity>1 ) {
				if(!is_null(json_decode($item->stock_id))){
					foreach (json_decode($item->stock_id) as $stockId){

						$totalExVatPrice+=getStockDetatils($stockId)->total_price_ex_vat;
						$psTotalSalePrice+=getStockDetatils($stockId)->sale_price;
						$psTotalVat+=getStockDetatils($stockId)->marg_vat;
						$totalCosts += getStockDetatils($stockId)->total_cost_with_repair;
						$totalPurchaseCost += getStockDetatils($stockId)->total_cost_with_repair;
						$purchasePrice+=getStockDetatils($stockId)->purchase_price;
						$purchasePriceStock+=getStockDetatils($stockId)->purchase_price;
						$vatType=getStockDetatils($stockId)->vat_type;
						$totalUnlockCost+=getStockDetatils($stockId)->unlock_cost;
						$totalPartCost+=getStockDetatils($stockId)->part_cost;
						$totalRepairCost+=getStockDetatils($stockId)->total_repair_cost-getStockDetatils($stockId)->part_cost;
						$totalSalePriceDelivery+=getStockDetatils($stockId)->sale_price;



						if($platform===Stock::PLATFROM_MOBILE_ADVANTAGE || $platform===Stock::PLATFROM_EBAY )
						{
							$itemsPrice=$item['individual_item_price']*$item['quantity'];
						}else{
							$itemsPrice=$item['individual_item_price'];
						}



						$taxRate = $item->tax_percentage * 100 > 0 ? ($item->tax_percentage) : 0;

						if(getStockDetatils($stockId)->ps_model){
							if(!is_null(getStockDetatils($stockId)->supplier_id)){


								if(!is_null(getStockDetatils($stockId)->supplier->recomm_ps)){
									$totalItemsSoldPS++;
									$supplierPre=getStockDetatils($stockId)->supplier->recomm_ps;
									array_push($recomeePer,getStockDetatils($stockId)->supplier->recomm_ps.'%');
									if(getStockDetatils($stockId)->vat_type===Stock::VAT_TYPE_STD){
										$est=(getStockDetatils($stockId)->total_price_ex_vat*getStockDetatils($stockId)->supplier->recomm_ps)/100;
										$estProfitSPModel+=$est;
									}else{
										$salePrice=getStockDetatils($stockId)->sale_price - getStockDetatils($stockId)->marg_vat;
										$estM=($salePrice*getStockDetatils($stockId)->supplier->recomm_ps)/100;
										$estProfitSPModel+=$estM;

									}

								}
							}

						}else{
							$totalItemsSoldNonPS++;
							$estProfitNonSPModel+=getStockDetatils($stockId)->true_profit - $sale->platform_fee;

						}

					}
				}
			}else{

				$tProfit=0;
				$tTrueProfit=0;
				foreach ($item->stock()->get() as $stock) {
					$totalExVatPrice+=$stock->total_price_ex_vat;
					$psTotalSalePrice+=$stock->sale_price;
					$psTotalVat+=$stock->marg_vat;
					$totalCosts += $stock->total_cost_with_repair;
					$totalPurchaseCost += $stock->total_cost_with_repair;
					$purchasePrice+=$stock->purchase_price;
					$purchasePriceStock +=$stock->purchase_price;
					$tProfit+=$stock->profit;
					$tTrueProfit+=$stock->true_profit;
					$vatType=$stock->vat_type;
					$totalUnlockCost+=$stock->unlock_cost;
					$totalPartCost+=$stock->part_cost;
					$totalRepairCost+=$stock->total_repair_cost-$stock->part_cost;
					$itemsPrice+=$item['individual_item_price'];
					$totalSalePriceDelivery+=$stock->sale_price;
					if($stock->ps_model){
						if(!is_null($stock->supplier_id)){
							if(!is_null($stock->supplier->recomm_ps)){
								$totalItemsSoldPS++;
								array_push($recomeePer,$stock->supplier->recomm_ps.'%');

								$supplierPre=$stock->supplier->recomm_ps;

								if($stock->vat_type===Stock::VAT_TYPE_STD){
									$est=($stock->total_price_ex_vat*$stock->supplier->recomm_ps)/100;
									$estProfitSPModel+=$est;
								}else{
									$salePrice=$stock->sale_price - $stock->marg_vat;
									$estM=($salePrice*$stock->supplier->recomm_ps)/100;
									$estProfitSPModel+=$estM;
								}
							}
						}

					}else{
						$totalItemsSoldNonPS++;
						$estProfitNonSPModel+=$stock->true_profit - $sale->platform_fee;
					}
					$taxRate = $item->tax_percentage * 100 > 0 ? ($item->tax_percentage) : 0;
				}


				 $ftProfit+=$tProfit;
				$ftTrueProfit+=$tTrueProfit;


			}

			if ($item->tax_percentage * 100 > 0 || !$item->tax_percentage * 100  && $vatType==="Standard" ) {
				$vatType = "Standard";
				array_push($vatTypeList,$vatType);

			} else {
				$vatType = "Margin";
				array_push($vatTypeList,$vatType);
			}
			$calculations = calculationOfProfitEbay($taxRate, $itemsPrice, $totalCosts, $vatType,$purchasePriceStock);
			if($vatType===Stock::VAT_TYPE_STD){

				$totalProfit=($sale->invoice_total_amount/1.2) - $totalPurchaseCost;

                $totalTrueProfit=$sale->invoice_total_amount/1.2 - $totalPurchaseCost;
                $totalExVat= $sale->invoice_total_amount/1.2;
			}else{
				$totalExVat+= $calculations['total_price_ex_vat'];
				$totalTrueProfit += $calculations['true_profit'];
				$totalProfit+=$calculations['profit'];
				$totalSalePrice+=$itemsPrice;
				$totalVatMargin+= $calculations['marg_vat'];

			}

//            if($sale->id===16525){
//                dd($calculations);
//            }
//			$totalExVat+= $calculations['total_price_ex_vat'];
//			$totalTrueProfit += $calculations['true_profit'];
//			$totalProfit+=$calculations['profit'];
//			$totalSalePrice+=$itemsPrice;
//			$totalVatMargin+= $calculations['marg_vat'];

//            if($item->tax_percentage * 100 > 0 || !$item->tax_percentage * 100  && $vatType==="Standard"){
//                $totalProfit+=($sale->invoice_total_amount/1.2) - $totalPurchaseCost;
//                $totalTrueProfit+=$sale->invoice_total_amount/1.2 - $totalPurchaseCost;
//                $totalExVat+= $sale->invoice_total_amount/1.2;
//            }else{
//                $totalProfit+=$sale->invoice_total_amount - $totalPurchaseCost-($sale->delivery_charges*20/100);
//                $totalVatMargin += ((($totalSalePriceDelivery-$purchasePrice)*16.67)/100);
//                $totalTrueProfit+=($sale->invoice_total_amount - $totalPurchaseCost-$sale->delivery_charges*20/100)- ((($totalSalePriceDelivery-$purchasePrice)*16.67)/100);
//                $totalSalePrice+=$sale->invoice_total_amount - $sale->delivery_charges*20/100;
//            }
//			$totalExVat+= $calculations['total_price_ex_vat'];
//			$totalTrueProfit += $calculations['true_profit'];
//			$totalProfit+=$calculations['profit'];
//			$totalSalePrice+=$itemsPrice;
//			$totalVatMargin+= $calculations['marg_vat'];

//            if($item->tax_percentage * 100 > 0 || !$item->tax_percentage * 100  && $vatType==="Standard"){
//                $totalProfit+=($sale->invoice_total_amount/1.2) - $totalPurchaseCost;
//                $totalTrueProfit+=$sale->invoice_total_amount/1.2 - $totalPurchaseCost;
//                $totalExVat+= $sale->invoice_total_amount/1.2;
//            }else{
//                $totalProfit+=$sale->invoice_total_amount - $totalPurchaseCost-($sale->delivery_charges*20/100);
//                $totalVatMargin += ((($totalSalePriceDelivery-$purchasePrice)*16.67)/100);
//                $totalTrueProfit+=($sale->invoice_total_amount - $totalPurchaseCost-$sale->delivery_charges*20/100)- ((($totalSalePriceDelivery-$purchasePrice)*16.67)/100);
//                $totalSalePrice+=$sale->invoice_total_amount - $sale->delivery_charges*20/100;
//            }
		}
	}


	if(!is_null($sale->delivery_charges)){
		$totalProfit=0;
		$totalTrueProfit=0;
		$totalVatMargin=0;
		$totalExVat=0;
		$totalSalePrice=0;


		if($item->tax_percentage * 100 > 0 || !$item->tax_percentage * 100  && $vatType==="Standard"){
			$totalProfit+=($sale->invoice_total_amount/1.2) - $totalPurchaseCost;
			$totalTrueProfit+=$sale->invoice_total_amount/1.2 - $totalPurchaseCost;
			$totalExVat+= $sale->invoice_total_amount/1.2;
		}else{
			$totalProfit+=$sale->invoice_total_amount - $totalPurchaseCost-($sale->delivery_charges*20/100);
			$totalVatMargin += ((($totalSalePriceDelivery-$purchasePrice)*16.67)/100);
			$totalTrueProfit+=($sale->invoice_total_amount - $totalPurchaseCost-$sale->delivery_charges*20/100)- ((($totalSalePriceDelivery-$purchasePrice)*16.67)/100);
			$totalSalePrice+=$sale->invoice_total_amount - $sale->delivery_charges*20/100;
		}

	}
}else{
	foreach($sale->stock as $stock)
	{
		$totalExVat+= $stock->total_price_ex_vat;
	}

	foreach($sale->stock as $stock)
	{
		$totalPurchasePrice+= $stock->purchase_price;
	}
	foreach($sale->stock as $stock)
	{
		$totalProfit+= $stock->profit;
	}
	foreach($sale->stock as $stock)
	{
		$totalTrueProfit+= $stock->true_profit;
	}

	foreach($sale->stock as $stock)
	{
		$totalSalePrice+= $stock->sale_price;
	}

	foreach($sale->stock as $stock)
	{
		$totalVatMargin+= $stock->marg_vat;
	}

	foreach($sale->stock as $stock)
	{
		$totalPurchaseCost+= $stock->total_cost_with_repair;
	}

	foreach ($sale->stock as $stock){
		$purchasePrice+=$stock->purchase_price;
	}

	foreach($sale->stock as $stock)
	{
		$totalSalePriceDelivery+= $stock->sale_price;
	}
	foreach($sale->stock as $stock)
	{
		$totalUnlockCost+= $stock->unlock_cost;
	}
	foreach($sale->stock as $stock)
	{
		$totalPartCost+= $stock->part_cost;
	}
	foreach($sale->stock as $stock)
	{
		$totalRepairCost+= $stock->total_repair_cost-$stock->part_cost;
		$totalExVatPrice+=$stock->total_price_ex_vat;
		$psTotalSalePrice+=$stock->sale_price;
		$psTotalVat+=$stock->marg_vat;

		if($stock->ps_model){
			if(!is_null($stock->supplier_id)){
				if(!is_null($stock->supplier->recomm_ps)){
					$totalItemsSoldPS++;
					$supplierPre=$stock->supplier->recomm_ps;
					array_push($recomeePer,$stock->supplier->recomm_ps.'%');
					if($stock->vat_type===Stock::VAT_TYPE_STD){
						$est=($stock->total_price_ex_vat*$stock->supplier->recomm_ps)/100;
						$estProfitSPModel+=$est;
					}else{

						$salePrice=$stock->sale_price - $stock->marg_vat;
						$estM=($salePrice*$stock->supplier->recomm_ps)/100;
						$estProfitSPModel+=$estM;

					}

				}
			}

		}else{
			$totalItemsSoldNonPS++;
			$estProfitNonSPModel+=$stock->true_profit - $sale->platform_fee;
		}

	}

//    if(!is_null($sale->delivery_charges)){
	$totalProfit=0;
	$totalTrueProfit=0;
	$totalVatMargin=0;
	$totalExVat=0;
	$totalSalePrice=0;
	if(count($sale->stock)){
		if($sale->stock[0]->vat_type==="Margin"){

			$totalProfit+=$sale->invoice_total_amount - $totalPurchaseCost-($sale->delivery_charges*20/100);


			$totalVatMargin += ((($totalSalePriceDelivery-$purchasePrice)*16.67)/100);

			$totalTrueProfit+=($sale->invoice_total_amount - $totalPurchaseCost- $sale->delivery_charges*20/100)- ((($totalSalePriceDelivery-$purchasePrice)*16.67)/100);
			$totalSalePrice+=$sale->invoice_total_amount - $sale->delivery_charges*20/100;

		}else{

			$totalProfit+=($sale->invoice_total_amount/1.2) - $totalPurchaseCost;
			$totalTrueProfit+=$sale->invoice_total_amount/1.2 - $totalPurchaseCost;
			$totalExVat+= $sale->invoice_total_amount/1.2;



		}
	}
	// }
}
?>

<tr data-sale-id="{{ $sale->id }}"
	@if($sale->invoice_status == Invoice::STATUS_PAID) class="success"
	@elseif(Auth::user()->type === 'admin' && $sale->device_locked) class="danger"
	@elseif(Auth::user()->type === 'admin' && $sale->amount == 0) class="success"
	@elseif($sale->picked && $sale->invoice_status == Invoice::STATUS_PAID && Auth::user()->type === 'admin') class="success-light"
	@elseif($sale->picked && Auth::user()->type === 'admin') class="warning"
		@endif>
	<td><a href="{{ route('sales.single', ['id' => $sale->id]) }}"><i class="fa fa-eye"></i> Details  </a><br>
		{{$sale->id}}
	</td>
	@if (Auth::user()->type !== 'user')
		<td>
			@if($sale->other_recycler)
				<b>Recycler:</b>
			@else

				@if($sale->customer_api_id)
					@if(isset($customers[$sale->customer_api_id]))
						<a href="{{ route('admin.users.single', ['id' => $sale->user_id]) }}">{{ $customers[$sale->customer_api_id]->full_name }}<br>
							{{$customers[$sale->customer_api_id]->company_name}}
						</a>

					@else
						-
					@endif
				@else
					<b class="text-danger">Error - No Customer</b>
				@endif
			@endif
		</td>
		<td>{{$sale->buyers_ref}}</td>
		<td>@if($sale->other_recycler)
				<b>{{ $sale->other_recycler }}</b>
			@else
				@if($sale->customer_api_id)


					@if($postCode !=="")
						{{$postCode}}

					@else

						@if(isset($customers[$sale->customer_api_id]))
							@if(isset($customers[$sale->customer_api_id]->shipping_address->postcode))
								{{ strtoupper($customers[$sale->customer_api_id]->shipping_address->postcode) }}
							@elseif(isset($customers[$sale->customer_api_id]->billing_address->postcode))
								{{ strtoupper($customers[$sale->customer_api_id]->billing_address->postcode) }}
							@else
								-
							@endif
						@else
							-
						@endif
					@endif
				@else
					<b class="text-danger">Error - No Customer</b>
				@endif
			@endif
		</td>
	@endif
	<td>{{ $sale->created_at->format('Y-m-d H:i') }}</td>
	<td>
		Stock:{{ count($sale->stock) }}
		@if($sale->batch) <a href="{{ route('batches.single', ['id' => $sale->batch->id]) }}">Batch #{{ $sale->batch->id }}</a>@endif
	</td>
	<td width="7%">
		{{--@if(count($sale->stock))--}}
		@if(Auth::user()->type === 'admin' && $sale->stock()->count() && $sale->stock()->first()->batch_id)
			<a href="{{ route('batches.single', ['id' => $sale->stock()->first()->batch_id]) }}">Batch no. {{ $sale->stock()->first()->batch->id }} {{ $sale->stock()->first()->batch->name ? "- ".$sale->stock()->first()->batch->name : null }}</a>
		@else
			<?php
			$nameList=[];
			foreach ($sale->stock->pluck('name')->toArray() as $name){
				array_push($nameList,str_replace( array('@rt'), 'GB', $name));
			}
			?>

			@if(strlen(implode(', ', $sale->stock->pluck('name')->toArray())) > 60)
				{{substr(implode(', ', $nameList),0,60)}}
				<span class="read-more-show hide_content">More<i class="fa fa-angle-down"></i></span>

				<span class="read-more-content"> {{substr(implode(', ', $nameList),60,strlen(implode(', ', $nameList)))}}
            <span class="read-more-hide hide_content">Less <i class="fa fa-angle-up"></i></span> </span>
			@else
				{{implode(', ', $nameList)}}
			@endif

		@endif

		@if($sale->item_name)

			<p class="show-read-more">   {{  str_replace( array('@rt'), 'GB', $sale->item_name)}}</p>
		@endif
	</td>
	<td>

		<?php
		// $sale->ebay_orders[0]->EbayOrderItems[0]->stock->vat_type
		$vatType='';
		if(count($sale->ebay_orders)){
			if($sale->ebay_orders[0]->EbayOrderItems[0]->quantity>1){
				if(!is_null(json_decode($sale->ebay_orders[0]->EbayOrderItems[0]->stock_id))){
					foreach (json_decode($sale->ebay_orders[0]->EbayOrderItems[0]->stock_id) as $stockId){
						$vatType=getStockDetatils($stockId)->vat_type;
					}
				}

			}else{

				if(!is_null($sale->ebay_orders[0]->EbayOrderItems[0]->stock)){
					$vatType=$sale->ebay_orders[0]->EbayOrderItems[0]->stock->vat_type;
				}


			}
		}

		?>
		@if(count($sale->ebay_orders))
			@if(count(array_unique($vatTypeList))>1)
				Mixed
			@else

				@if($sale->ebay_orders[0]->EbayOrderItems[0]['tax_percentage']*100 > 0 ||  !$sale->ebay_orders[0]->EbayOrderItems[0]['tax_percentage']*100  && $vatType==="Standard" )

					Standard
				@else
					Margin
				@endif
			@endif
		@else
			{{count($sale->stock) ? $sale->stock[0]->vat_type:'-'}}
		@endif
	</td>

	<td class="invoice-total-amount">


		@if(count($sale->ebay_orders))
			@if(is_null($sale->delivery_charges)|| $sale->ebay_orders[0]->EbayOrderItems[0]['tax_percentage']*100 > 0 ||  !$sale->ebay_orders[0]->EbayOrderItems[0]['tax_percentage']*100  && $vatType==="Standard")
				{{ $sale->amount ? $sale->amount_formatted : "Replacements" }}
			@else
				{{ money_format($sale->amount) }}
			@endif
		@else
			@if(count($sale->stock))
				@if(is_null($sale->delivery_charges)|| $sale->stock[0]->vat_type==="Standard")
					{{ $sale->amount ? $sale->amount_formatted : "Replacements" }}
				@else
					{{  money_format($sale->amount-($sale->delivery_charges*20/100)) }}

				@endif
			@endif
		@endif



	</td>

	<td class="invoice-total-amount-ex-vat" width="5%">


		@if(count($sale->ebay_orders))

			@if($sale->ebay_orders[0]->EbayOrderItems[0]['tax_percentage']*100 > 0 ||  !$sale->ebay_orders[0]->EbayOrderItems[0]['tax_percentage']*100  && $vatType==="Standard")
				{{money_format($sale->amount/1.2) }}

			@else
				-
			@endif
		@else
			@if(isset($sale->stock[0]) && $sale->stock[0]->vat_type==="Standard")
                {{money_format($sale->amount/1.2) }}

            @else
                -
            @endif
		@endif


	</td>

	@if(Auth::user()->type === 'admin')

		<?php
		$title='';
		$title.="Unlock Cost:".money_format($totalUnlockCost) ."\n";
		$title .="Part Cost:".money_format($totalPartCost)."\n" ;
		$title .="Repair Cost:".money_format($totalRepairCost)."\n" ;


		?>

		<td @if($totalPurchaseCost < 0) class="text-danger" @endif>
			{{ money_format($totalPurchaseCost)  }}
			@if($totalPurchaseCost!==$purchasePrice)
				<span><i class="fa fa-asterisk text-danger" aria-hidden="true" title="{{$title}}" style="font-size: 8px;"></i></span>

			@endif
		</td>
		<td @if($totalProfit < 0) class="total-profit text-danger" @else class="total-profit" @endif>

			@if(count(array_unique($vatTypeList))>1)
				{{money_format($ftProfit)}}

			@else
				@if(!is_null($sale->delivery_charges))
					{{money_format($totalProfit) }}
				@else
					{{money_format($totalProfit) }}

				@endif

			@endif

{{--			@if(!is_null($sale->delivery_charges))--}}

{{--				{{money_format(config('app.money_format'), $totalProfit) }}--}}
{{--			@else--}}
{{--				{{money_format(config('app.money_format'), $totalProfit) }}--}}
{{--			@endif--}}
		</td>
	@endif

	<?php


	if(count($sale->ebay_orders)){
		if(count(array_unique($vatTypeList))>1){
			$profit=$totalSalePrice?($ftProfit/$totalSalePrice) * 100:0 ;
		}else{
			if($sale->ebay_orders[0]->EbayOrderItems[0]['tax_percentage']*100 > 0 ||  !($sale->ebay_orders[0]->EbayOrderItems[0]['tax_percentage']*100)  && $vatType==="Standard"){


				$profit=$totalExVat ?(number_format($totalProfit,2)/number_format($totalExVat,2)) * 100:0 ;
			}else{

				$profit=$totalSalePrice?($totalProfit/$totalSalePrice) * 100:0 ;
			}
		}
	}else{
		if(isset($sale->stock[0]) && $sale->stock[0]->vat_type === "Standard"){
			$profit=$totalExVat ?($totalProfit/$totalExVat) * 100:0 ;


		}else{
			$profit=$totalSalePrice?($totalProfit/$totalSalePrice) * 100:0 ;
		}
	}
	?>
	<td
			@if($profit < 0) class="total-profit-per text-danger" @else class="total-profit-per" @endif>
		{{number_format($profit,2)."%"}}
	</td>
	<td @if($totalVatMargin < 0) class="vat-margin text-danger" @else class="vat-margin" @endif>

		@if(count(array_unique($vatTypeList))>1)
			{{ money_format($psTotalVat)}}
		@else
				@if(count($sale->ebay_orders))
					@if(!$sale->ebay_orders[0]->EbayOrderItems[0]['tax_percentage']*100  && $vatType==="Margin")
						{{money_format($totalVatMargin) }}

					@endif
				@else
					@if(isset($sale->stock[0]) && $sale->stock[0]->vat_type==="Margin")
                    {{money_format($totalVatMargin) }}
                @else - @endif

				@endif
		@endif
	</td>

	<td @if($totalTrueProfit < 0) class="true-profit text-danger" @else class="true-profit" @endif>
		@if(count(array_unique($vatTypeList))>1)
			{{money_format($ftTrueProfit)  }}

		@else
		{{money_format($totalTrueProfit)  }}

		@endif

	</td>

	<?php
	if(count($sale->ebay_orders)){
		if(count(array_unique($vatTypeList))>1){
			$trueProfit=$totalSalePrice?($ftTrueProfit/$totalSalePrice) * 100:0 ;
		}else{

			if($sale->ebay_orders[0]->EbayOrderItems[0]['tax_percentage']*100 > 0 ||  !$sale->ebay_orders[0]->EbayOrderItems[0]['tax_percentage']*100  && $vatType==="Standard"){

				$trueProfit=$totalExVat ?(number_format($totalTrueProfit,2)/number_format($totalExVat,2)) * 100:0 ;
			}else{

				$trueProfit=$totalSalePrice?($totalTrueProfit/$totalSalePrice) * 100:0 ;

			}
		}


	}else{

		if(isset($sale->stock[0]) && $sale->stock[0]->vat_type === "Standard"){
			$trueProfit=$totalExVat ?($totalTrueProfit/$totalExVat) * 100:0 ;
		}else{
			$trueProfit=$totalSalePrice?($totalTrueProfit/$totalSalePrice) * 100:0 ;
		}
	}
	?>
	<td class="platform">
		{{$sale->platform}}<br>
		@if($sale->platform === Stock::PLATFROM_MOBILE_ADVANTAGE)
			@if(isset($sale->ebay_orders))
				@if(count($sale->ebay_orders()->get()))
					{{$sale->ebay_orders()->get()[0]['payment_method']}}
					@if(!is_null($sale->ebay_orders()->get()[0]['payment_type']))

						({{$sale->ebay_orders()->get()[0]['payment_type']}})
					@endif

				@endif
			@endif
		@endif
	</td>
	<td @if($trueProfit < 0) class="true-profit-pre text-danger" @else class="true-profit-pre" @endif>
		{{number_format($trueProfit,2)."%"}}
	</td>
	<td class="invoice-status">
		@include('sales.invoice-status')
	</td>

	<td>


        @if(!is_null($sale->deliveryNote))

                @if($sale->invoice_status ==="dispatched")
                    <a href="{{route('sales.delivery-note',['id'=>$sale->id])}}">	Delivery Note</a>
                @else
                    -
                @endif
        @endif

	</td>

	<td
			data-creation-status="{{ $sale->invoice_creation_status }}"
			data-creation-status-finished="{{ $sale->invoice_creation_status_finished ? 'true' : 'false' }}">
		@if (!$sale->invoice_creation_status_finished)
			<img class="invoice-in-progress" src="{{ asset('/img/ajax-loader.gif') }}" aria-hidden="true">
		@endif

		<span class="status">
			@if ($sale->invoice_creation_status === 'success')
				<a href="{{ route('sales.invoice', $sale->id) }}" target="blank">
					Invoice #{{!is_null($sale->invoice_doc_number)? $sale->invoice_doc_number.'-':'' }} {{ $sale->invoice_number }}
				</a>
			@elseif($sale->other_recycler)
				Recyclers Order #{{ $sale->recyclers_order_number ? : '-' }}
			@else
				{{ $sale->invoice_creation_status_alt }}
			@endif
		</span>
		@if(Auth::user()->type === 'admin' && $sale->item_name && $sale->vat_type)
			<span><b>Custom Order</b></span>
		@endif
	</td>

	<td class="platform_fee">

		<form action="{{route('sales.shipping_cost')}}" method="post">
			<input type="hidden" name="_token" value="{{{ csrf_token() }}}"/>
			<input type="hidden" name="sale_id" value="{{$sale->id}}">
			<div class="input-group">
				<input type="text" name="platform_fee" value="{{!is_null($sale->platform_fee) ? $sale->platform_fee:0}}"
					   class="form-control value" id="fee_{{$sale->id}}">
				<span class="input-group-btn">
			<button class="btn   btn-success"><i class="fa fa-check"></i></button>
		</span>
			</div>
		</form>


	</td>
	<td class="shipping_cost">


		<form action="{{route('sales.shipping_cost')}}" method="post">
			<input type="hidden" name="_token" value="{{{ csrf_token() }}}"/>
			<input type="hidden" name="sale_id" value="{{$sale->id}}">
			<div class="input-group">
				<input type="text" name="shipping_cost" value="{{!is_null($sale->shipping_cost) ? $sale->shipping_cost:0}}"
					   class="form-control value" id="shipping_{{$sale->id}}">
				<span class="input-group-btn"><button class="btn btn-success"><i class="fa fa-check"></i></button></span>
			</div>
		</form>
	</td>
	<?php
	if(count(array_unique($vatTypeList))>1){
		$estProfit=  $ftTrueProfit-$sale->platform_fee-$sale->shipping_cost;
	}else{
		$estProfit=  $totalTrueProfit-$sale->platform_fee-$sale->shipping_cost;
	}

	?>
	<td @if($estProfit < 0) class="estProfit text-danger" @else class="estProfit" @endif>
		{{ money_format($estProfit) }}

	</td>
	<td class="estProfitPre">

		@if(abs($estProfitSPModel)>0)
			@if(abs($estProfitNonSPModel) >0)
				{{money_format($estProfitNonSPModel+ $sale->delivery_charges) }}

			@else
				N/A
			@endif
		@else

			{{money_format($estProfit)}}
		@endif
	</td>
	<td>
		@if(abs($estProfitSPModel)>0)

			@if($totalItemsSoldPS && !$totalItemsSoldNonPS)
				{{money_format(($estProfit *$supplierPre)/100)}}

			@else
				{{money_format($estProfitSPModel + $sale->delivery_charges- $sale->shipping_cost)}}

			@endif

		@else
			N/A
		@endif

	</td>
	<td style="text-align: center">

		{{$totalItemsSoldNonPS}}
	</td>
	<td style="text-align: center">
		{{$totalItemsSoldPS}}

	</td>

	<td>
		<?php
		$psNonModel=0;
		$psModel=0;
		if(abs($estProfitNonSPModel)>0){
			$psNonModel=$estProfitNonSPModel+$sale->delivery_charges;
		}

		if(abs($estProfitSPModel)>0){
			$psModel = ($estProfitSPModel + $sale->delivery_charges)- $sale->shipping_cost;
		}

		?>


		@if($estProfitSPModel>0)

			@if($totalItemsSoldPS && !$totalItemsSoldNonPS)
				{{money_format(($estProfit *$supplierPre)/100)}}

			@else
				{{money_format(($psNonModel) + ($psModel))}}
			@endif

		@else
			{{money_format($estProfit)}}

		@endif
	</td>
	<?php

	$fVatType='';
	$finaNetProfit=0;

	if($totalItemsSoldPS && !$totalItemsSoldNonPS)
	{
		$totalNetProfit=($estProfit *$supplierPre)/100;
	}elseif($estProfitSPModel>0)
	{
		$totalNetProfit=$psNonModel + $psModel;
	}
	else
	{
		$totalNetProfit=$estProfit;
	}


	if(count($sale->ebay_orders)){
		if($sale->ebay_orders[0]->EbayOrderItems[0]['tax_percentage']*100 > 0 ||  !$sale->ebay_orders[0]->EbayOrderItems[0]['tax_percentage']*100  && $vatType==="Standard"){
			$fVatType=Stock::VAT_TYPE_STD;
		}else{
			$fVatType=Stock::VAT_TYPE_MAG;
		}
	}else{
		if(isset($sale->stock[0]) && $sale->stock[0]->vat_type==="Standard"){
			$fVatType=Stock::VAT_TYPE_STD;
		}else{
			$fVatType=Stock::VAT_TYPE_MAG;
		}
	}


	if($fVatType===Stock::VAT_TYPE_STD){
		$finaNetProfit= $totalExVatPrice>0?  ($totalNetProfit/$totalExVatPrice)*100:0;
	}else{
		$totalVatAndSalePrice=$psTotalSalePrice-$psTotalVat;
		$finaNetProfit=$totalVatAndSalePrice>0 ? ($totalNetProfit/$totalVatAndSalePrice)*100:0;
	}

	?>
	<td>

		{{number_format($finaNetProfit,2).'%'  }}
	</td>
	<td>
		@if (Auth::user()->canPayForSale($sale))
			{!! Form::open(['route' => 'sales.select-payment-method']) !!}
			{!! Form::hidden('id', $sale->id) !!}
			{!! Form::submit('Pay', ['class' => 'btn btn-info btn-xs btn-block mb5']) !!}
			{!! Form::close() !!}
		@endif
		@if (Auth::user()->canVoidSale($sale) && $sale->invoice_status != Invoice::STATUS_DISPATCHED)
			{!! Form::open(['route' => 'sales.cancel', 'class' => 'cancel-sale']) !!}
			{!! Form::hidden('id', $sale->id) !!}
			{!! Form::submit('Cancel', ['class' => 'btn btn-danger btn-xs btn-block mb5']) !!}
			{!! Form::close() !!}
		@endif
		@if ($sale->tracking_number)
			<span class="small text-muted"> Tracking number set: {{ $sale->tracking_number }}</span>
		@elseif(!$sale->tracking_number && $sale->courier && Auth::user()->type == 'admin')
			<span class="small text-muted">Courier: {{ $sale->courier }}</span>
		@elseif (!$sale->tracking_number && Auth::user()->type !== 'user' && ($sale->invoice_status === Invoice::STATUS_DISPATCHED || $sale->other_recycler))
			<a href="javascript:" class="add-tracking-button btn btn-xs btn-default btn-block">Add Tracking Number</a>
		@endif
	</td>
</tr>
