<?php
use App\Batch;
?>
@if(!count($batches))
	<div class="alert alert-info">Nothing Found</div>
@else
	<table class="table table-hover table-striped text-center">
		<tr>
			<th></th>
			<th class="text-center">Batch Number</th>
			<th class="text-center">No. Items</th>
			<th class="text-center">Batch Name</th>
			<th class="text-center">Date Created</th>
			<th class="text-center">Status</th>
			<th class="text-center">Purchase Price</th>
			<th class="text-center">Batch Sales Price</th>
			<th class="text-center">No. Offers</th>
			<th class="text-center">Details</th>
			<th class="text-center"><i class="fa fa-lg fa-trash" data-toggle="tooltip" title="Delete Batch (status->for sale and not connected with any auction only)"></i></th>
		</tr>
		@foreach($batches as $batch)
			<tr>
				<td>
					@if(!$batch->custom_name)
						{!! Form::checkbox('batch_ids['.$batch->id.']') !!}
					@else
						<i class="fa fa-info" data-toggle="tooltip" title="Custom Batch"></i>
					@endif	
				</td>
				<td>{{ $batch->id }}</td>
				<td>{{ $batch->stock()->count() }}</td>
				<td>{{ $batch->name }}</td>
				<td>{{ $batch->created_at->format('Y-m-d') }}</td>
				<td>
					{{ $batch->status }}
					@if($batch->status == Batch::STATUS_FOR_SALE && $batch->end_time_formatted)
						<br/><small>End Time: {{ $batch->end_time_formatted }}</small>
						@if($batch->extended_time)
							<br/><small>End Time was updated (+5 minutes)</small>
						@endif
					@endif
				</td>
				<td>{{ $batch->stock->sum('total_costs') > 0 ? money_format(config('app.money_format'), $batch->stock->sum('total_costs')) : '' }}</td>
				<td>{{ $batch->sale_price > 0 ? money_format(config('app.money_format'), $batch->sale_price) : '' }}</td>
				<td><a href="{{ route('batches.deal-sheet', ['id' => $batch->id]) }}">{{ $batch->batch_offers()->count() }}</a></td>
				<td><a class="btn btn-block btn-sm btn-primary" href="{{ route('batches.single', ['id' => $batch->id]) }}">Details</a></td>
				<td>
					@if($batch->deletable)
						{!! BsForm::open(['method' => 'post', 'route' => 'batches.delete']) !!}
							{!! BsForm::hidden('id', $batch->id) !!}
							{!! BsForm::button('<i class="fa fa-trash"></i>', ['type' => 'submit', 'class' => 'btn-sm btn-block btn-danger confirmed', 'data-confirm' => 'Batch will be deleted']) !!}
						{!! BsForm::close() !!}
					@endif
				</td>
			</tr>
		@endforeach
	</table>
@endif
