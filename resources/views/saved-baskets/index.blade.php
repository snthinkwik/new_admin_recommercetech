@extends('app')

@section('title', 'Saved Baskets')

@section('content')

	<div class="container">

		<h2>Saved Baskets</h2>

		<div class="row">

			<div class="col-md-12">

				@if(!count($savedBaskets))
					<div class="alert alert-danger">Nothing Found</div>
				@else
					<table class="table table-hover table-bordered">
						<tr>
							<th>ID</th>
							<th>No. Items</th>
							<th>Total Purchase Value</th>
							<th>Total Sales Value</th>
							<th>Created At</th>
							<th>Details</th>
						</tr>
						@foreach($savedBaskets as $savedBasket)
							<tr>
								<td>{{ $savedBasket->id }}</td>
								<td>{{ $savedBasket->stock()->count() }}</td>
								<td>{{ $savedBasket->total_purchase_price_formatted }}</td>
								<td>{{ $savedBasket->total_sale_price_formatted }}</td>
								<td>{{ $savedBasket->created_at->format('d/m/Y H:i:s') }}</td>
								<td><a class="btn btn-sm btn-default btn-block" href="{{ route('saved-baskets.single', ['id' => $savedBasket->id]) }}">Details</a></td>
							</tr>
						@endforeach
					</table>
				@endif

				{!! $savedBaskets->render() !!}

			</div>


		</div>

	</div>

@endsection