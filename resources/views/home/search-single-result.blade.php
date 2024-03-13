@if(isset($items))
	<div class="row">
		<div class="col-md-9 col-md-offset-3">
			<div class="panel panel-primary">
				<div class="panel-body">
					@foreach($params as $key=>$val)
						<small><b>{{ ucfirst($key) }}</b>: {{ $val ? $val : 'Any' }}</small><br/>
					@endforeach

					<b>Quantity Available: </b>{{ $items }}
					@if($items > 0)
						<br/><b>From: </b> {{ money_format($from) }}
					@endif

					@if($items > 0)
						{!! BsForm::open(['method' => 'post', 'route' => 'home.add-to-basket', 'id' => 'add-to-basket-form']) !!}
							@foreach($params as $name=>$value)
								{!! BsForm::hidden($name, $value) !!}
							@endforeach
							<div class="input-group">
								<span class="input-group-addon">Quantity</span>
								{!! Form::number('quantity', null, ['placeholder' => 'Quantity', 'min' => 1, 'max' => $items, 'step' => 1, 'class' => 'form-control', 'required' => 'required']) !!}
								<span class="input-group-btn">{!! BsForm::submit('Add to Basket', ['id' => 'add-to-basket-submit']) !!}</span>
							</div>
						{!! BsForm::close() !!}

						@if(Auth::user()->type == 'admin')
							@if($inTesting > 0)
								<hr/>
								<b>Quantity In Testing: {{ $inTesting }}</b>
								{!! BsForm::open(['method' => 'post', 'route' => 'home.add-to-basket', 'id' => 'add-to-basket-form']) !!}
									{!! BsForm::hidden('in_testing', true)  !!}
									@foreach($params as $name=>$value)
										{!! BsForm::hidden($name, $value) !!}
									@endforeach
									<div class="input-group">
										<span class="input-group-addon">Quantity</span>
										{!! Form::number('quantity', null, ['placeholder' => 'Quantity', 'min' => 1, 'max' => $inTesting, 'step' => 1, 'class' => 'form-control', 'required' => 'required']) !!}
										<span class="input-group-btn">{!! BsForm::submit('Add Testing to Basket', ['id' => 'add-to-basket-submit']) !!}</span>
									</div>
								{!! BsForm::close() !!}
							@endif
							<hr/>
							<b>Bulk Update Price</b>
							{!! BsForm::open(['method' => 'post', 'route' => 'home.bulk-update-price', 'disabled']) !!}
								@if(!$params->grade)
									<fieldset disabled="disabled">
								@endif
								@foreach($params as $name=>$value)
									{!! BsForm::hidden($name, $value) !!}
								@endforeach
								<div class="input-group">
									<span class="input-group-addon">Price</span>
									{!! Form::number('price', null, ['placeholder' => 'Price', 'min' => 1, 'step' => 0.5, 'class' => 'form-control']) !!}
									<span class="input-group-btn">{!! BsForm::submit('Update Price') !!}</span>
								</div>
								@if(!$params->grade)
									</fieldset>
									<b class="text-danger">Grade must be specified to update price</b>
								@endif
							{!! BsForm::close() !!}
						@endif
					@endif
				</div>
			</div>
		</div>
	</div>
@endif
