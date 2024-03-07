@extends('app')

@section('title', 'Batch Overview')

@section('content')
	<div class="container">
		@include('messages')
		<a class="btn btn-sm btn-default" href="{{ route('batches.single', ['id' => $batch->id]) }}"><i class="fa fa-reply"></i> Return to Batch #{{ $batch->id }}</a>
		<div class="row">
			<div class="col-md-4">
				<div class="panel panel-default">
					<div class="panel-heading">
						Overview
					</div>
					<div class="panel-body">
						<table class="table table-hover table-striped">
							<tr>
								<th>Cost price:</th>
								<td class="text-right">£{{ $items->totalCost }}</td>
							</tr>
							<tr>
								<th>Sale price:</th>
								<td class="text-right">£{{ $items->totalSale }}</td>
							</tr>
							@if(isset($sale))
								<th>Batch sale price:</th>
								<td class="text-right">£{{ $sale->invoice_total_amount }}</td>
							@endif
						</table>
						<p class="text-info"><b>Sale price</b> is sum of each item's sale price.</p>
						@if($items->countSale < $items->totalCount && !isset($sale))
							<p class="text-danger">{{ $items->totalCount-$items->countSale }} item(s) have empty <b>Sale Price</b></p>
						@endif
						@if(isset($sale))
							<p class="text-info"><b>Batch sale price</b> price for which Batch has been sold</p>
						@endif
					</div>
				</div>
			</div>
			<div class="col-md-4">
				<div class="panel panel-default">
					<div class="panel-heading">
						Networks
					</div>
					<div class="panel-body">
						<table class="table table-striped table-hover">
							@foreach($networks as $network)
								<tr>
									<th>{{ $network->network }}</th>
									<td class="text-center">{{ number_format(($network->count/$items->totalCount*100),2) }}%</td>
									<td class="text-right">({{ $network->count }}/{{ $items->totalCount }})</td>
								</tr>
							@endforeach
						</table>
					</div>
				</div>
			</div>
			<div class="col-md-4">
				<div class="panel panel-default">
					<div class="panel-heading">
						Grades
					</div>
					<div class="panel-body">
						<table class="table table-striped table-hover">
							@foreach($grades as $grade)
								<tr>
									<th>{{ $grade->grade }}</th>
									<td class="text-center">{{ number_format(($grade->count/$items->totalCount*100),2) }}%</td>
									<td class="text-right">({{ $grade->count }}/{{ $items->totalCount }})</td>
								</tr>
							@endforeach
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
@endsection