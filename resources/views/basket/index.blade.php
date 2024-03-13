@extends('app')

@section('title', 'Basket')

@section('content')

	<div class="container">
		@include('messages')

		@if (!count($basket->toArray()) && !is_null($part_basket))
			<div class="alert alert-info">Your basket is empty.</div>
		@endif


		@if(count($errors))
			<div class="alert alert-danger">
			@foreach ($errors->all() as $error)
				<p>{{ $error }}</p>
			@endforeach
			</div>
		@endif

		<p>
			<a href="{{ route('stock') }}" class="btn btn-default btn-sm">Go back to Stock</a>
		</p>

		{!! BsForm::open(['route' => 'basket.empty', 'id' => 'basket-empty-form', 'class' => 'ib']) !!}
			{!! BsForm::groupSubmit('Empty basket', ['class' => 'btn-warning']) !!}
		{!! BsForm::close() !!}

		<button id="create-sale-basket" class="btn btn-default">
			{{ Auth::user() ? Auth::user()->texts['sales']['create'] : 'Create sale' }}
		</button>

		<div class="row">
			<div class="col-md-12">
				@if(count($basket) > 0)
					<div class="panel panel-default">
						<div class="panel-heading"><h2 class="panel-title">Items</h2></div>
						<div class="panel-body">
							<table class="table table-hover table-striped">
								<tr>
									<th>Name</th>
									<th>Price</th>
									@if(Auth::user() && Auth::user()->type !== 'user')
										<th>Serial</th>
										<th>IMEI</th>
										<th>RCT Ref</th>
										<th>3rd-party ref</th>
									@endif
									<th>Remove from basket</th>
								</tr>
								@foreach($basket as $item)
									<tr>
										<td>{{ $item->long_name }}</td>
										<td>{{ $item->sale_price_formatted }}</td>
										@if (Auth::user() && Auth::user()->type !== 'user')
											<td>{{ $item->serial ?: '-' }}</td>
											<td>{{ $item->imei ?: '-' }}</td>
											<td><a href="{{ route('stock.single', ['id' => $item->id]) }}">{{ $item->our_ref }}</a></td>
											<td>{{ $item->third_party_ref }}</td>
										@endif
										<td>
											{!! BsForm::open(['route' => 'basket.delete']) !!}
											{!! BsForm::hidden('id', $item->id) !!}
											{!! BsForm::groupSubmit('Remove from basket', ['class' => 'btn-warning btn-xs']) !!}
											{!! BsForm::close() !!}
										</td>
									</tr>
								@endforeach
							</table>
						</div>
					</div>
				@endif

				@if(!is_null($part_basket))
					<div class="panel panel-default">
						<div class="panel-heading"><h2 class="panel-title">Parts</h2></div>
						<div class="panel-body">
							<table class="table table-hover table-striped">
								<tr>
									<th>Name</th>
									<th>Quantity</th>
									<th>Single Price</th>
									<th>Total Part Price</th>
									<th>Remove from basket</th>
								</tr>
								@foreach($part_basket as $item)
									<td>{{ $item->part->long_name }}</td>
									<td>
										{{ $item->quantity }}
										@if($item->part->quantity < $item->quantity)
											<span class="text-danger">Quantity in stock is less than quantity you want to buy.</span>
										@endif
									</td>
									<td>{{ money_format($item->part->sale_price) }}</td>
									<td>{{ money_format($item->part_total_amount) }}</td>
									<td>
										{!! BsForm::open(['method' => 'post', 'route' => 'part-basket.delete']) !!}
										{!! BsForm::hidden('id', $item->part->id) !!}
										{!! BsForm::groupSubmit('Remove from basket', ['class' => 'btn-warning btn-xs']) !!}
										{!! BsForm::close() !!}
									</td>
								@endforeach
							</table>
						</div>
					</div>
				@endif
			</div>
		</div>
	</div>

@endsection

@section('nav-right')
	<div class="navbar-form navbar-right pr0">
		<div class="btn-group">
			<button id="create-sale" class="btn btn-default">
				{{ Auth::user() ? Auth::user()->texts['sales']['create'] : 'Create sale' }}
			</button>
		</div>
	</div>
	<div id="basket-wrapper" class="navbar-right pr0">
		@include('basket.navbar')
	</div>
@endsection
