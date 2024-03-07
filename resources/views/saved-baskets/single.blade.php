@extends('app')

@section('title', 'Saved Basket')

@section('content')

	<div class="container">

		<a class="btn btn-default" href="{{ route('saved-baskets') }}"><i class="fa fa-reply"></i> Back to List</a>

		<h2>Saved Basket #{{ $savedBasket->id }}</h2>

		@include('messages')

		<table class="table  table-striped">
			<tr>
				<td>
					<p>Created at: {{ $savedBasket->created_at->format('d/m/Y H:i:s') }}</p>
					<p>Total Purchase Value: {{ $savedBasket->total_purchase_price_formatted }}</p>
					<p>Total Sales Value: {{ $savedBasket->total_sale_price_formatted }}</p>
				</td>
				<td>
					<p>Total Profit: {{ count($totalProfit) > 0 ? money_format(config('app.money_format'), array_sum($totalProfit)):'-' }}</p>
					<p>True Profit:{{ count($totalTrueProfit) > 0 ? money_format(config('app.money_format'), array_sum($totalTrueProfit))  :'-' }}</p>
				</td>
				<td>
					<p>Total Profit %: {{$profitPercentage ? $profitPercentage.'%':'-' }}</p>
				<p>True Profit %:{{ $trueProfitPercentage  ? $trueProfitPercentage.'%':'-' }}</p>
				</td>
			</tr>

		</table>





		{!! BsForm::open(['method' => 'post', 'route' => 'sales.new']) !!}
			{!! BsForm::hidden('id', $savedBasket->id) !!}
			@foreach($savedBasket->stock as $stock)
				{!! BsForm::hidden('ids[]', $stock->id) !!}
			@endforeach
			{!! BsForm::groupSubmit('Create Sale') !!}
		{!! BsForm::close() !!}

		{!! BsForm::open(['method' => 'post', 'route' => 'saved-baskets.delete']) !!}
			{!! BsForm::hidden('id', $savedBasket->id) !!}
			{!! BsForm::groupSubmit('Delete Basket', ['class' => 'confirmed btn-danger', 'data-confirm' => 'Basket will be removed']) !!}
		{!! BsForm::close() !!}

		<div class="row">

			<div class="col-md-12">

				@if(!count($savedBasket->stock))
					<div class="alert alert-danger">No Items Found</div>
				@else
					<table class="table table-hover table-bordered">
						<tr>
							<th>RCT Ref</th>
							<th>Title</th>
							<th>GB</th>
							<th>Grade</th>
							<th>Network</th>
							<th>3rd Party Ref</th>
							<th>IMEI</th>
							<th>Status</th>
							<th>VAT Type</th>
							<th>Touch ID/ Face id</th>
							<th>Cracked Back</th>
							<th class="text-center"><i class="fa fa-remove"></i></th>
						</tr>
						@foreach($savedBasket->stock as $stock)
							<tr>
								<td><a href="{{ route('stock.single', ['id' => $stock->id]) }}">{{ $stock->our_ref }}</a></td>
								<td>{{ $stock->name }}</td>
								<td>{{$stock->capacity_formatted}}</td>
								<td>{{$stock->grade}}</td>
								<td>{{$stock->network}}</td>
								<td>{{$stock->third_party_ref}}</td>
								<td>{{ $stock->imei ? : $stock->serial}}</td>
								<td>{{ $stock->status }}</td>
								<td>{{$stock->vat_type}}</td>
								<td>{{$stock->touch_id_working}}</td>
								<td>{{$stock->cracked_back}}</td>
								<td>
									{!! BsForm::open(['method' => 'post', 'route' => 'saved-baskets.delete-from-basket']) !!}
										{!! BsForm::hidden('id', $savedBasket->id) !!}
										{!! BsForm::hidden('stock_id', $stock->id) !!}
										{!! BsForm::button('<i class="fa fa-remove"></i>', ['class' => 'btn-danger confirmed btn-block btn-sm', 'data-confirm' => 'Item will be removed from basket', 'type' => 'submit']) !!}
									{!! BsForm::close() !!}
								</td>
							</tr>
						@endforeach
					</table>
				@endif

			</div>


		</div>

	</div>

@endsection