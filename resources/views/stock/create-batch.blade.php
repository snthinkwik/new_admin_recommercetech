@extends('app')

@section('title', 'Create Batch')

@section('content')
	<div class="container">
		@include('messages')
		<div class="row">
			<div class="col-md-4">
				<a class="btn btn-default btn-block" data-toggle="collapse" data-target="#addToBatch">Add to Batch</a>
				<div class="panel panel-default panel-body collapse" id="addToBatch">
					@if(count($batches)>=1)
						{!! Form::open(['route' => 'stock.create-add-batch']) !!}
							@foreach($items as $item)
								{!! Form::hidden('items['.$item->id.']') !!}
							@endforeach
							<div class="form-group btn-group" data-toggle="buttons">
								@foreach($batches as $batch)
								<label class="btn btn-primary btn-sm">
									{!! Form::radio('batch', $batch->id) !!} {{ $batch->id }}
								</label>
								@endforeach
							</div>
							{!! Form::submit('Add', ['class' => 'btn btn-primary btn-block']) !!}
						{!! Form::close() !!}
					@else
						<p class="text-info">No Available Batches</p>
					@endif
				</div>
			</div>
			<div class="col-md-4">
				<a class="btn btn-default btn-block" data-toggle="collapse" data-target="#createBatch">Create Batch</a>
				<div class="panel panel-default panel-body collapse" id="createBatch">
					{!! Form::open(['route' => 'stock.create-new-batch']) !!}
						@foreach($items as $item)
							{!! Form::hidden('items['.$item->id.']') !!}
						@endforeach
						@if($batchesList->count())
							<div class="alert alert-info">
								<p><b>Default number would be {{ $batchesList->last()->id+1 }}.</b></p>
							</div>
						@endif
						{!! Form::label('id', 'Custom Batch Number - leave empty for default') !!}
						{!! Form::number('id',null, ['class' => 'form-control', 'min' => 1]) !!}
						{!! Form::submit('Create', ['class' => 'btn btn-primary btn-block']) !!}
					{!! Form::close() !!}
				</div>
			</div>
			<div class="col-md-4">
				<a class="btn btn-default btn-block" data-target="#items" data-toggle="collapse">Show Items <span class="badge">{{ count($items) }}</span></a>
				<div class="panel panel-default panel-body collapse" id="items">
					@foreach($items as $item)
						<p><a target="_blank" href="{{ route('stock.single', ['id' => $item->id]) }}">{{ $item->long_name }}</a></p>
					@endforeach
				</div>
			</div>
		</div>
	</div>
@endsection

@section('nav-right')
	<div id="basket-wrapper" class="navbar-right pr0">
		@include('basket.navbar')
	</div>
@endsection