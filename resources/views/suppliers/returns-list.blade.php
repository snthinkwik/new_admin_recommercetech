@if(!count($supplierReturns))
	<div class="alert alert-info">Nothing Found</div>
@else
	<table class="table table-bordered table-hover">
		<tr>
			<th>ID</th>
			<th>Supplier</th>
			<th>Status</th>
			<th>Third Party Ref</th>
			<th>No. Items</th>
			<th>Items</th>
			<th>Note</th>
			<th>Total Purchase</th>
			<th>Created At</th>
		</tr>
		@foreach($supplierReturns as $return)

			<tr>
				<td><a href="{{ route('suppliers.return-single', ['id' => $return->id]) }}">{{ $return->id }}</a></td>
				<td>{{ $return->supplier->name }}</td>
				<td>{{ $return->status }}</td>
				<td>@foreach($return->items as $item)  {{ $item->stock->third_party_ref }}<br/> @endforeach</td>
				<td>{{ $return->items()->count() }}</td>
				<td>@foreach($return->items as $item) <a href="{{ route('stock.single', ['id' => $item->stock_id]) }}">{{ $item->stock->our_ref }} - {{ $item->stock->name }}</a><br/> @endforeach</td>
				<td>


					@if(strlen($return->note) > 60)
						{{substr($return->note,0,60)}}
										<span class="read-more-show hide_content">More<i class="fa fa-angle-down"></i></span>

						<span class="read-more-content"> {{substr($return->note,60,strlen($return->note))}}
							<span class="read-more-hide hide_content">Less <i class="fa fa-angle-up"></i></span> </span>
					@else

						{{$return->note}}

					@endif



{{--					@if($sale->item_name)<p class="show-read-more">   {{  $return->note)}}</p>@endif--}}


					</td>
				<td>
					<?php
						$total=0;
						foreach ($return->items as $item){
							$total+=$item->stock->total_cost_with_repair;
						}
						?>
					{{money_format($total)}}

				</td>
				<td>{{ $return->created_at->format('d/m/y H:i:s') }}</td>
			</tr>
		@endforeach
	</table>
@endif
