@extends('app')

@section('title', 'Batches Summary')

@section('content')

	<div class="container">

		<a href="{{ route('batches') }}" class="btn btn-default"><i class="fa fa-reply"></i> Back to list</a>

		<h2>Batches Summary</h2>

		<div class="row">
			@if(!$batches)
				<div class="alert alert-info">Nothing Found</div>
			@else
				<div class="col-md-6">
					<a class="btn btn-default" id="batch-summary-copy-button">Copy for What's App</a>
					<div>
						@foreach($batches as $batch)
							Batch {{ $batch->batch->id }} - *{{ $batch->batch->name }}*<br/>
							Devices count: {{ $batch->items->sum('quantity') }}<br/>
							@foreach($batch->items as $item)
								{{ $item->quantity }}x {{ $item->name }} - {{ $item->capacity_formatted }} @if($item->network != "Not Applicable")- {{ $item->network_formatted }}@endif<br/>
							@endforeach
							<br/>Take All - {{ money_format(config('app.money_format'), $batch->sale_price) }}
							<br/>View here: {{ $batch->batch->trg_uk_url }}
							<br/>
							<br/>
						@endforeach
					</div>
				</div>
				<div class="col-md-6">
<textarea id="batch-summary-textarea" style="height:0; width:0;">
@foreach($batches as $batch)
		Batch {{ $batch->batch->id }} - *{{ $batch->batch->name }}*
		Devices count: {{ $batch->items->sum('quantity') }}
		@foreach($batch->items as $item)
			{{ $item->quantity }}x {{ $item->name }} - {{ $item->capacity_formatted }} @if($item->network != "Not Applicable")- {{ $item->network_formatted }}@endif
		@endforeach

		Take All - {{ money_format(config('app.money_format'), $batch->sale_price) }}
		View here: {{ $batch->batch->trg_uk_url }}
	@endforeach
</textarea>
				</div>
			@endif
		</div>
	</div>

@endsection