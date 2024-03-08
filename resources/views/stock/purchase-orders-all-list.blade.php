<?php
use Illuminate\Http\Request;
use App\Models\Stock;


$data=[];
foreach ($orders as $order){
    if($reitem_unsold==="No"){
        if(!$order->items_to_sell){
            array_push($data,$order);
        }
	}else{
        array_push($data,$order);
	}

}

?>
@foreach($data as $order)



	<tr @if( $order->items_to_sell<1 ) style="background-color: #DCDCDC	" @endif>
		<td>{{ $order->purchase_order_number }}</td>
		<td>{{$order->supplier}}</td>
		<td>{{ $order->purchase_date }}</td>
		<td>{{ $order->items }}</td>
		<td>{{ money_format($order->total_sales_price)  }}</td>
		@if($order->vat_type===Stock::VAT_TYPE_STD)
		<td>{{ money_format($order->total_sales_price/1.2) }}</td>
		@else
			<td>N/A</td>
		@endif
		<td>{{ money_format( $order->total_purchase_price) }}</td>
		<td>{{ money_format( $order->total_purchase_price_cost) }}</td>
		<td>{{ $order->vat_type }}</td>


		<td>{{ $order->items_sold }}</td>
		<td>{{ $order->items_returned }}</td>
		<td>{{ money_format( $order->total_return_supplier_purchas) }}</td>
		<td>{{money_format($order->net_purchase_price)}}</td>
		<td>
			@if($order->vat_type===Stock::VAT_TYPE_STD)
				{{ money_format( ($order->total_sales_price/1.2)- $order->net_purchase_price) }}
			@else
			{{ money_format( $order->total_profit) }}
			@endif

		</td>
        <td>@if($order->vat_type!=="Standard"){{money_format($order->vat_margin)}}@else N/A @endif</td>
		<td>

			@if($order->vat_type===Stock::VAT_TYPE_STD)
				{{ money_format(($order->total_sales_price/1.2)- $order->net_purchase_price) }}
			@else
				{{money_format($order->total_true_profit)}}
			@endif


		</td>
		<td>
			@if($order->vat_type===Stock::VAT_TYPE_STD)
				<?php
					$ty=$order->total_sales_price/1.2;
					$tProfit=($order->total_sales_price/1.2)- $order->net_purchase_price
				?>

			@if(abs($order->total_true_profit)>0)
				{{ number_format(($tProfit/$ty)*100, 2) }}%
			@endif
			@else
				@if($order->total_true_profit>0)
				{{ number_format(($order->total_true_profit/$order->total_sales_price)*100, 2) }}%
					@endif
			@endif

			</td>
		<td>{{money_format($order->final_cost) }}</td>
		<td>

			@if($order->vat_type===Stock::VAT_TYPE_STD)
				{{money_format($tProfit-$order->final_cost) }}
			@else
				{{money_format($order->total_true_profit-$order->final_cost) }}
			@endif


		</td>
		<td>

			@if($order->vat_type===Stock::VAT_TYPE_STD)

				<?php
					$yu=$tProfit-$order->final_cost;
				?>
				@if(abs($order->est_profit)>0)
				{{number_format(($yu/($order->total_sales_price/1.2))*100,2) .'%'}}
					@endif
			@else
				<?php
				$tP=$order->total_true_profit-$order->final_cost;
				?>
				@if(abs($order->est_profit>0))

				{{number_format(($tP/$order->total_sales_price)*100,2) .'%'}}
				@endif
			@endif

			</td>
		<td>{{ $order->items_to_sell }}</td>
		<td>{{money_format($order->total_sales_stock_price)}}</td>

{{--		<td>{{money_format($order->profit)}}</td>--}}



		<td>{{ $order->items_in_repair }}</td>
		<td>{{money_format($order->repair_cost)  }}</td>
		<td>{{$order->total_unlock_qty}}</td>
		<td>{{money_format($order->total_unlock_cost)}}</td>
		<td>{{$order->total_b2b_qty}}</td>
		<td>{{$order->total_retail_qty}}</td>
		<td>{{$order->delete_lost}}</td>

		<td>{!! $order->country ? '<img src="'.$order->country.'" alt="">' : 'Mixed' !!}</td>
		<td><a class="btn btn-sm btn-default" href="{{ route('stock.purchase-order-stats', [ 'purchase_order_number' => $order->purchase_order_number ]) }}">Details</a></td>
	</tr>
@endforeach
