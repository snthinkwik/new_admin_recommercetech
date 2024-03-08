<?php
use App\Models\Stock;
$grades = ['' => ' - '] + Stock::getAvailableGradesWithKeys();
$vat_types = ['Margin' => 'Margin', 'Standard' => 'Standard'];
$test_status=[];
?>
@extends('app')

@section('title', Auth::user() ? Auth::user()->texts['sales']['create'] : 'Create sale')

@section('content')
	<div class="container">
		<div class="row">
			<div class="col-md-4">
				@include('messages')
				<div class="alert alert-success" role="alert" style="display:none;" id="success-message">
					<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    Price SuccessFully Updated
				</div>

				@if($option == 'otherRecycler')
					<h4>Sell to Other Recycler</h4>

					{!! Form::open(['route' => 'sales.summary-other','class'=>'verificationForm2' ,'method' => 'get']) !!}
				@else
					{!! Form::open(['route' => 'sales.summary','class'=>'verificationForm2' ,'method' => 'post']) !!}
				@endif
				@if(count($items))
					<h4>Items</h4>
					<div class="form-group">
						<p class="text-info">Grade will be changed when form is submitted</p>
						<div class="input-group">
							<span class="input-group-addon">Bulk Update Grade</span>
							{!! BsForm::select('grade', $grades, null) !!}
						</div>
					</div>

					{{--<div class="form-group">--}}
						{{--<div class="input-group">--}}
							{{--<span class="input-group-addon">PlatForm</span>--}}
							{{--{!! BsForm::select('platform', $platformList,'Recomm', ['class'=>'platform']) !!}--}}
						{{--</div>--}}
					{{--</div>--}}



					<div class="form-group">
						<div class="input-group">
							<span class="input-group-addon">&pound;</span>
							{!! BsForm::number('all_items_price', 0, ['step' => 0.01, 'id' => 'all-items-price']) !!}
							<span class="input-group-btn">{!! BsForm::button('Set All Items Price', ['id' => 'all-items-price-button']) !!}


								{{--<button class="update">Update</button>--}}
								<span class="p10">
								{!! BsForm::button('Update', ['class' => 'update']) !!}
								</span>

							</span>

						</div>

					</div>

					<p class="text-info">Sale price will be updated when form is submitted</p>
					@foreach ($items as $item)
						<h5>
							<?php
								if(is_null($item->test_status)){
									$status="no";
								}else{
									$status=$item->test_status;

								}

								array_push($test_status,$status);
							?>

							<a target="_blank" href="{{ route('stock.single', ['id' => $item->id]) }}">{{ $item->long_name  }}
							@if(isset($item->product->non_serialised) && $item->product->non_serialised)
								({{ number_format($item->product->multi_quantity)}})
							@endif</a>
						</h5>
						@if (Auth::user() && Auth::user()->type !== 'user')
							@if(isset($item->product->non_serialised))
								@if($item->product->non_serialised)
									<div class="d-flex form-group align-items-center"><p class="cu-mb-2 mr-2">Quantity:</p>
										<div class="form-group">
											<div class="input-group">{!! BsForm::text('items[' . $item->id . '][qty]',1,null) !!}</div>
										</div>
									</div>
									@endif
							@endif
							<p>RCT Ref: {{ $item->our_ref  }}</p>
							<p>3rd-party ref: {{ $item->third_party_ref }}</p>
							<p>IMEI: {{ $item->imei }}</p>
							<p>Colour: {{ $item->colour }}</p>
							<p>Touch/Face ID Working?: {{ $item->touch_id_working }}</p>
							<p>Cracked Back : {{ $item->cracked_back  }}</p>
							<div class="d-flex form-group align-items-center"><p class="cu-mb-2 mr-2">VAT Type:</p>
								<div class="form-group">
									<div class="input-group">
										{!! BsForm::select('vat_type', $vat_types,$item->vat_type, ['class'=>'vatTypes','required' => 'required']) !!}


									</div>
								</div>
									 </div>

							<p>Purchase Price: {{ money_format($item->purchase_price)  }}</p>
							<p>Unlock Cost: {{money_format($item->unlock_cost)  }}</p>
							<p>Repair cost : {{ money_format($item->total_repair_cost) }}</p>
							<p>Total Purchase Price: {{ $item->total_cost_with_repair }}</p>
							<div class="d-flex form-group align-items-center">
								<p class="cu-mb-2 mr-2">Sale Price:</p>
								<div>
									<div class="@hasError(items.$item->id.price)">
										<div class="form-group">
										<div class="input-group">
											<div class="input-group-addon custom-group">£</div>

											{!! Form::text('items[' . $item->id . '][price]', $item->sale_price ?: null, ['class' => 'form-control prices', 'placeholder' => 'Price','id'=>$item->id]) !!}

											<input type="hidden" value="{{$item->id}}" class="ids">
										</div>
										</div>
										@error("items.$item->id.price") @enderror
									</div>
								</div>
							</div>
							@if($item->vat_type ==="Standard")
								<p>Sales Price Ex Vat:  {{ money_format($item->total_price_ex_vat)  }}  </p>
							@endif


							<p><span class="p2">Profit:  {{  money_format($item->profit)   }}</span>
								<span class="p45"> Profit%: @if($item->vat_type==="Standard" && $item->total_price_ex_vat)
										{{number_format($item->profit/$item->total_price_ex_vat*100,2)."%"}}
									@elseif($item->sale_price)
										{{number_format($item->profit/$item->sale_price*100,2)}}
									@endif
								  </span>
							</p>
							@if($item->vat_type !=="Standard")
								<p><span class="p2">True Profit: {{  money_format($item->true_profit)   }}</span>
									<span class="p10">True Profit%: @if($item->vat_type==="Standard" && $item->total_price_ex_vat )
											{{$item->total_price_ex_vat? number_format($item->true_profit/$item->total_price_ex_vat*100,2)  ."%":''}}
										@elseif($item->sale_price)
											{{$item->sale_price ? number_format($item->true_profit/$item->sale_price*100,2) ."%":''}}</span>
									@endif</p>


							@endif
						@endif
						<hr>
					@endforeach


				<input type="hidden" id="network" value="{{$item->network}}">
					<input type="hidden" value="{{json_encode($test_status)}}" id="test_status">
				@endif

				@if(!is_null($parts))
					<h4>Parts</h4>
					@foreach($parts as $part)
						<p>{{ $part->quantity }}x {{ $part->part->long_name }}, {{ money_format($part->part->sale_price) }} each</p>
					@endforeach
				@endif
				{!! Form::submit('Go to summary', ['class' => 'btn btn-primary','id'=>'summary']) !!}
				{!! Form::close() !!}

			</div>
		</div>
	</div>
@endsection

@section('nav-right')
	<div id="basket-wrapper" class="navbar-right pr0">
		@include('basket.navbar')
	</div>
@endsection

@section('pre-scripts')
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
	<script>

		$("#summary").on('click',function (e){
			e.preventDefault();
			var status=$("#test_status").val();
			var network=$("#network").val();
			const myArr = JSON.parse(status);

			if(network==="Sim Locked"){

				let text = "One of the devices in this sale is locked to a network. Please unlock before dispatch.";
				if (confirm(text) == true) {
					$(".verificationForm2").submit()
				}
               false;
			}
			for (let i = 0; i < myArr.length; i++) {

				let text = "This Item Is Not In Testing Complete Status";
				if(myArr[i]!="Complete"){
					if (confirm(text) == true) {
						$(".verificationForm2").submit()
					}else{
						return false;
					}
				}else{
					$(".verificationForm2").submit()
				}

			}
			false;
		});
        $(".update").on('click',function (e) {

            e.preventDefault();
            var values=[];
            var ids=[];
            var vatTypeList=[];

            $(".prices").each(function() {
				values.push($(this).val())
			});
            $(".ids").each(function() {
                ids.push($(this).val())
            });

            $(".vatTypes").each(function () {
				vatTypeList.push($(this).val());
            });


            $.ajax({
                type: "POST",
                url: "{{route('sales.update-price')}}",
                data: {value:values,ids:ids,vat_type:vatTypeList},
                cache: false,
                success: function(data){
                    if(data.error){
                        alert(data.message);
					}else{
                       $("#success-message").slideDown();
                       location.reload();
					}


                }
            });

            return false;






		});
	</script>
	@endsection
