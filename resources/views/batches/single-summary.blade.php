@extends('app')

@section('title', 'Batch Summary')

@section('content')
	<div class="container">
		@include('messages')
		<a class="btn btn-sm btn-default" href="{{ route('batches.single', ['id' => $batch->id]) }}"><i class="fa fa-reply"></i> Return to Batch #{{ $batch->id }}</a>
		<a class="btn btn-sm btn-primary" href="{{ route('batches.single-summary-export', ['id' => $batch->id]) }}"><i class="fa fa-download"></i> Export XLS</a>
		<h2>Batch #{{ $batch->id }} Summary</h2>
		<div class="row">
			@if(!count($items))
				<div class="alert alert-info">Nothing Found</div>
			@else
				<div class="col-md-6">
					<a class="btn btn-default" id="batch-summary-copy-button">Copy for What's App</a>
					<div>
						Batch {{ $batch->id }} - {{ $batch->name }}<br/>
						Device count: {{ $batch->stock()->count() }}<br/>
						@foreach($items as $item)
							{{ $item->quantity }}x {{ $item->name }} - {{ $item->capacity_formatted }} @if($item->network != "Not Applicable")- {{ $item->network }}@endif<br/>
						@endforeach
						<br/>Take All - {{ money_format($batch->sale_price) }}
					</div>
				</div>
				<div class="col-md-6">
<textarea id="batch-summary-textarea" style="height:0; width:0;">
Batch {{ $batch->id }} - {{ $batch->name }}
Device count: {{ $batch->stock()->count() }}
	@foreach($items as $item)
		{{ $item->quantity }}x {{ $item->name }} - {{ $item->capacity_formatted }} - @if($item->network != "Not Applicable")- {{ $item->network }}@endif
	@endforeach

Take All - {{ money_format($batch->sale_price) }}
</textarea>
				</div>
			@endif
		</div>
	</div>
@endsection
