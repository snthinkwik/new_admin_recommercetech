<?php
use App\Unlock\Pricing;
$networks = Pricing::getAvailableNetworks();
$models = Pricing::getAvailableModels();
?>
@extends('app')

@section('title', "Unlock your own stock - new order")

@section('content')

	<div class="container">
		@include('messages')
		<div class="row">
			<div class="col-md-4">
				{!! BsForm::open(['route' => 'unlocks.own-stock.new-order']) !!}
					{!!
						BsForm::groupTextarea(
							'imeis_list',
							old('imeis_list'),
							['placeholder' => 'One or more IMEI numbers, separated by new lines, spaces or commas...'],
							['label' => 'Enter IMEI', 'errors_name' => 'imeis', 'errors_all' => true]
						)
					!!}
					{!! BsForm::groupSelect('network', array_combine($networks, $networks)) !!}
					{!! BsForm::groupSelect('models', array_combine($models, $models)) !!}
					{!! BsForm::groupSubmit('Place Order', ['class' => 'btn-block'] ) !!}
				{!! BsForm::close() !!}
			</div>
			<div class="col-md-8">
				<h2>Pricing</h2>
				<table class="table table-striped">
					<thead>
						<tr><th>Network</th><th>Models</th><th>Price</th><th>Timeframe</th></tr>
					</thead>
					<tbody>
						@foreach ($pricing as $price)
							<tr>
								<td>{{ $price->networks }}</td>
								<td>{{ $price->models }}</td>
								<td>{{ $price->amount_before_vat_formatted }}</td>
								<td>{{ $price->timeframe }}</td>
							</tr>
						@endforeach
					</tbody>
				</table>
				<p>Prices exclude 20% VAT</p>
			</div>
		</div>
	</div>

@endsection