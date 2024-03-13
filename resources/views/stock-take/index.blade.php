@extends('app')

@section('title', 'Stock Take')

@section('content')

	<div class="container">
		<h2>Mark Items As Seen</h2>

		@include('messages')

		{!! BsForm::open(['method' => 'post', 'route' => 'stock-take.delete-all-stock-take-records']) !!}
			{!! BsForm::groupSubmit('Delete all stock take records', ['class' => 'btn-danger']) !!}
		{!! BsForm::close() !!}


		{!! BsForm::open(['route' => 'stock-take.mark-as-seen', 'id' => 'stock-take-mark-as-seen-form']) !!}
		{!!
			BsForm::groupTextarea(
				'stock_take_list',
				old('stock_take_list'),
				['placeholder' => 'One or more of the following [IMEI numbers, 3rd party ref, RCT Refs], separated by new lines, spaces or commas...'],
				['label' => 'Enter IMEI/3rd-party-ref/RCT-ref', 'errors_name' => 'imeis', 'errors_all' => true]
			)
		!!}
		{!! BsForm::groupSubmit('Mark As Seen', ['class' => 'btn-block']) !!}
		{!! BsForm::close() !!}

		<div class="panel panel-default">
			<div data-toggle="collapse" data-target="#items-sold" class="panel-heading">Items Scan <span class="badge">{{$stockCount}}</span></div>
			<div class="panel-body collapse" id="items-sold">
				<table class="table table-condensed table-small">
					<thead>
					<tr>
						<th>Ref</th>
						<th>Sku</th>
						<th>3rd-party ref</th>
						<th>Name</th>
						<th>Capacity</th>
						<th>Colour</th>
						<th>Condition</th>
						<th>Grade</th>
						<th>Network</th>
						<th>Status</th>
						<th>Sales price</th>
						<th>Purchase date</th>
						<th>Purchase price</th>
					</tr>
					</thead>
					<tbody>
									@foreach ($stock as $item)
					<tr>
												<td>{{ $item->our_ref }}</td>
												<td>
													<a href="{{ route('stock.single', ['id' => $item->id]) }}">
														{{ $item->sku }}
													</a>
												</td>
												<td>{{ $item->third_party_ref }}</td>
												<td>{{ $item->name }}</td>
												<td>{{ $item->capacity_formatted }}</td>
												<td>{{ $item->colour }}</td>
												<td>{{ $item->condition }}</td>
												<td>{{ $item->grade }}</td>
												<td>{{ $item->network }}</td>
												<td>{{ $item->status }}</td>
												<td>{{ $item->sale_price_formatted }}</td>
												<td>{{ $item->purchase_date ? $item->purchase_date : '' }}</td>
												<td>{{ $item->purchase_price_formatted }}</td>
					</tr>
									@endforeach
					</tbody>
				</table>
				<div id="stock-pagination-wrapper">{!! $stock->appends(Request::all())->render() !!}</div>
			</div>
		</div>

		<div class="panel panel-default">
			<div data-toggle="collapse" data-target="#items-not-scanned" class="panel-heading">Items Not Scan <span class="badge">{{$stockNotCount}}</span></div>
			<div class="panel-body collapse" id="items-not-scanned">
				<table class="table table-condensed table-small">
					<thead>
					<tr>
						<th>Ref</th>
						<th>Sku</th>
						<th>3rd-party ref</th>
						<th>Name</th>
						<th>Capacity</th>
						<th>Colour</th>
						<th>Condition</th>
						<th>Grade</th>
						<th>Network</th>
						<th>Status</th>
						<th>Sales price</th>
						<th>Purchase date</th>
						<th>Purchase price</th>
					</tr>
					</thead>
					<tbody>
					@foreach ($stockNot as $item)
						<tr>
							<td>{{ $item->our_ref }}</td>
							<td>
								<a href="{{ route('stock.single', ['id' => $item->id]) }}">
									{{ $item->sku }}
								</a>
							</td>
							<td>{{ $item->third_party_ref }}</td>
							<td>{{ $item->name }}</td>
							<td>{{ $item->capacity_formatted }}</td>
							<td>{{ $item->colour }}</td>
							<td>{{ $item->condition }}</td>
							<td>{{ $item->grade }}</td>
							<td>{{ $item->network }}</td>
							<td>{{ $item->status }}</td>
							<td>{{ $item->sale_price_formatted }}</td>
							<td>{{ $item->purchase_date ? $item->purchase_date : '' }}</td>
							<td>{{ $item->purchase_price_formatted }}</td>
						</tr>
					@endforeach
					</tbody>
				</table>

				<div id="stock-pagination-wrapper">{!! $stockNot->appends(Request::all())->render() !!}</div>
			</div>
		</div>
	</div>




@endsection
