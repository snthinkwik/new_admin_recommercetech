<form>


	<div class="row container">
		<div class="col-md-12">
	<strong class="text-success">Total Stock Qty :{{$totalQty + $unmpping+$unmappingWithZero}}</strong>
	<strong class="text-success">Mapped items:{{$totalQty}}</strong>
	<strong class="text-success">UnMapped items:{{$unmpping+$unmappingWithZero}}</strong>
<br>

			<strong class="text-success">Inbound Stock Qty:{{$totalInbound + $inBoundUnmpping+$inBoundUnmappingWithZero}}</strong>
			<strong class="text-success">Mapped items:{{$totalInbound}}</strong>
			<strong class="text-success">UnMapped items:{{$inBoundUnmpping+$inBoundUnmappingWithZero}}</strong>
		</div>



	</div>
	<div class="container table small stock table-h-sticky">
	<table class="table small">
		<caption>Stock overview</caption>
		<thead>
{{--		<tr>--}}

			<th class="totalBlank"></th>
			<th class="totalBlank"></th>
			<th class="totalBlank"></th>
			<th class="totalBlank"></th>
			<th class="totalBlank"></th>
			<th class="totalBlank"></th>
			<th class="totalBlank"></th>
			<th class="totalBlank"></th>
			<th class="totalBlank"></th>
			<th class="totalBlank"></th>

			<th class="totalHeader">Totals</th>
			<th class="totalHeader"  >{{ money_format($totalQtyPurchasePrice)}}</th>
			<th class="totalHeader" >{{$totalQtyInStock}}</th>
			<th class="totalHeader" >{{$totalQtyTested}}</th>
			<th class="totalHeader" >{{$totalOfInBound}}</th>
			<th class="totalHeader" >{{$totalQtyGradeA}}</th>
			<th class="totalHeader" >{{$totalQtyGradeB}}</th>
			<th class="totalHeader" >{{$totalQtyGradeC}}</th>
			<th class="totalHeader" >{{$totalQtyGradeD}}</th>
			<th class="totalHeader" >{{$totalQtyGradeE}}</th>
			<th class="totalHeader" >{{$totalQtyCrackBack}}</th>
			<th class="totalHeader" >{{$totalQtyTotalTouchId}}</th>
			<th class="totalHeader" >{{$totalQtyTotalLocked}}</th>
{{--		</tr>--}}
		<tr>


			<th style="text-align: center">Product Category</th>
			<th style="text-align: center">Make</th>
			<th style="text-align: center">Name</th>
			<th style="text-align: center">Recomm Product Id</th>
			<th style="text-align: center">Non Serialised</th>

			<th style="text-align: center">Model</th>
			<th style="text-align: center">Manufacturers SKU(MPN)</th>

			<th style="text-align: center">EAN</th>
			<th style="text-align: center">Grade</th>
			<th style="text-align: center">Status</th>
			<th style="text-align: center">Vat Type</th>
			<th style="text-align: center">Total Purchase Price</th>
			<th style="text-align: center">Average Purchase Price(ea)</th>
			<th style="text-align: center">Total Qty in Stock</th>
			<th style="text-align: center">Total Qty Tested</th>
			<th style="text-align: center">Inbound Qty</th>
			<th style="text-align: center">Grade A</th>
			<th style="text-align: center">Grade B</th>
			<th style="text-align: center">Grade C</th>
			<th style="text-align: center">Grade D</th>
			<th style="text-align: center">Grade E</th>
{{--			<th>Retail Stock</th>--}}
			<th style="text-align: center">Cracked Back</th>
			<th style="text-align: center">No Touch/Face ID</th>
			<th style="text-align: center">Network Locked</th>
			<th style="text-align: center">Retail Comparison</th>

		</tr>
		</thead>
		<tfoot>
		<tbody>
		@if(count($stock)>0)
			@foreach ($stock as $item)



				<tr>


					<td style="text-align: center" >{{$item->product_category}}</td>
					<td style="text-align: center" >{{$item->make}}</td>
					<td style="text-align: center" ><a href="{{route('products.single',['id'=>$item->product_id])}}">  {{$item->product_name}}</a></td>
					<td style="text-align: center" > @if($item->product_id){{$item->product_id}}@else - @endif</td>
					<td style="text-align: center" >@if($item->non_serialised) Yes @else No @endif</td>
					<td style="text-align: center" >{{ $item->model}}</td>
					<td style="text-align: center" width="10%" >
						@if(strpos($item->mpn, ',') !== false)
							<?php
							$mpn=explode(",",$item->mpn);
							?>
									@if(strlen(implode(',',  $mpn)) > 20)

									{{substr(implode(',', $mpn),0,20)}}
									<span class="read-more-show hide_content">More<i class="fa fa-angle-down"></i></span>

									<span class="read-more-content"> {{substr(implode(',', $mpn),20,strlen(implode(',', $mpn)))}}
										<span class="read-more-hide hide_content">Less <i class="fa fa-angle-up"></i></span> </span>
								@else
									{{implode(',', $mpn)}}

									@endif
						@elseif(strpos($item->mpn, ' ') !== false)
									<?php
									$mpn=explode(" ",$item->mpn);
									?>
								@if(strlen(implode(' ',  $mpn)) > 20)
									{{substr(implode(' ', $mpn),0,20)}}
									<span class="read-more-show hide_content">More<i class="fa fa-angle-down"></i></span>

									<span class="read-more-content"> {{substr(implode(' ', $mpn),20,strlen(implode(' ', $mpn)))}}
										<span class="read-more-hide hide_content">Less <i class="fa fa-angle-up"></i></span> </span>
										@else
											{{implode(' ', $mpn)}}
										@endif
						@else
							{{$item->mpn}}
						@endif


					</td>

					<td style="text-align: center">


						@if(strpos($item->ean, ',') !== false)
							@foreach(explode(",",$item->ean) as $mpn)
								{{$mpn}}<br>
							@endforeach
						@elseif(strpos($item->ean, ' ') !== false)
							@foreach(explode(" ",$item->ean) as $mpn)
								{{$mpn}}<br>
							@endforeach
						@else
							{{$item->ean}}
						@endif


					</td>
					<td style="text-align: center" >{{ $item->grade }}</td>
					<td style="text-align: center" >{{$item->status}}</td>

					<td style="text-align: center" >{{  $item->vat_type }}</td>
					<td  style=" text-align: center;">{{ money_format($item->total_purchase_price)   }}</td>
					<td style="text-align: center;border-right: 2px solid #000000;"> @if($item->qty_in_stock>0) {{ money_format($item->total_purchase_price/$item->qty_in_stock)  }} @else - @endif</td>
					<td  style="text-align: center"> <strong>{{$item->qty_in_stock}}</strong></td>
					<td  style="text-align: center">
						{{$item->qty_in_tested}}
					</td>
					<td style="text-align: center">{{$item->qty_in_bound}}</td>
					<td style="border-left: 2px solid #000000; text-align: center;">{{$item->grade_a}}</td>
					<td style="text-align: center" >{{$item->grade_b}}</td>
					<td style="text-align: center" >{{$item->grade_c}}</td>
					<td style="text-align: center" >{{$item->grade_d}}</td>
					<td style="border-right: 2px solid #000000; text-align: center">{{$item->grade_e}}</td>
					<td style="text-align: center">{{$item->cracked_back}}</td>
					<td style="text-align: center">{{$item->no_touch_face_id}}</td>
					<td style="text-align: center">{{$item->network_locked}}</td>
					<td style="text-align: center">@if($item->retail_comparison) Yes  @else No @endif </td>
				</tr>
			@endforeach
		@else

			<tr>
				<td colspan="19" align="center"><h5>No Data Found</h5></td>
			</tr>
		@endif
		</tbody>
	</table>
	</div>
</form>
