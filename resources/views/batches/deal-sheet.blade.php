@extends('app')

@section('title', 'Batch Deal Sheet')

@section('content')
	<div class="container">

		<h2>Batch <a href="{{ route('batches.single', ['id' => $batch->id]) }}">#{{ $batch->id }}</a> - {{ $batch->name }} - Deal Sheet</h2>

		@include('messages')

		{!! BsForm::open(['method' => 'post', 'route' => 'batches.deal-sheet-notify-best-price', 'class' => 'mb5 form-inline ']) !!}
			{!! BsForm::hidden('id', $batch->id) !!}
			{!! BsForm::submit('Notify Best Price', ['class' => 'confirmed', 'data-confirm' => 'Notify Best Price Emails and SMS will be sent']) !!}
			{!! BsForm::close() !!}

			{!! BsForm::open(['method' => 'post', 'route' => 'batches.deal-sheet-mark-all-as-seen', 'class' => 'mb5 form-inline']) !!}
			{!! BsForm::hidden('id', $batch->id) !!}
			{!! BsForm::submit('Mark all as seen', ['class' => 'confirmed', 'data-confirm' => 'All offers will be marked as seen']) !!}
			{!! BsForm::close() !!}

			{!! BsForm::open(['method' => 'post', 'route' => 'batches.deal-sheet-submit', 'class' => 'form-inline mb5']) !!}
			{!! BsForm::hidden('id', $batch->id) !!}
			<div class="form-group">
				<div class="input-group">
					<span class="input-group-addon">Customer ID</span>
					{!! BsForm::number('customer_id', null, ['min' => 1, 'required' => 'required']) !!}
				</div>
			</div>
			<div class="form-group">
				<div class="input-group">
					<span class="input-group-addon">Offer £</span>
					{!! BsForm::number('offer', null, ['step' => 0.01, 'min' =>1, 'required' => 'required']) !!}
				</div>
			</div>
			{!! BsForm::submit('Save') !!}
		{!! BsForm::close() !!}


		<table class="table table-hover table-striped">
			<tr>
				<th>User</th>
				<th>Customer ID</th>
				<th>Offer</th>
				<th>Profit</th>
				<th>Seen?</th>
				<th>Submitted</th>
				<th>Create Sale</th>
				<th>Delete</th>
			</tr>
			@foreach($batch->batch_offers()->orderBy('offer', 'desc')->get() as $offer)
				<tr>
					<td><a href="{{ route('admin.users.single', ['id' => $offer->user_id]) }}">{{ $offer->user->full_name }}</a></td>
					<td>{{ $offer->user->invoice_api_id }}</td>
					<td>{{ $offer->offer_formatted }}</td>
					<td>{{ money_format(config('app.money_format'), $offer->offer-$batch->stock()->get()->sum('total_costs')) }}</td>
					<td>{{ $offer->seen ? "Yes" : "No" }}
						@if(!$offer->seen)
							{!! BsForm::open(['method' => 'post', 'route' => 'batches.deal-sheet-mark-as-seen', 'class' => 'form-inline']) !!}
							{!! BsForm::hidden('id', $offer->id) !!}
							{!! BsForm::button('<i class="fa fa-check"></i>', ['type' => 'submit', 'class' => 'btn btn-xs btn-success']) !!}
							{!! BsForm::close() !!}
						@endif
					</td>
					<td>{{ $offer->created_at->format('d/m/y H:i:s') }}</td>
					<td>
						@if($batch->custom_name)
							{!! BsForm::open(['method' => 'get', 'route' => 'sales.custom-order']) !!}
						@else
							{!! Form::open(array('route' => 'sales.summary-batch')) !!}
						@endif
						{!! Form::hidden('batch', $batch->id) !!}
						@foreach($batch->stock as $item)
							{!! Form::hidden('items[' . $item->id . ']') !!}
						@endforeach

						{!! BsForm::hidden('price', $offer->offer) !!}
						{!! BsForm::hidden('customer_id', $offer->user->invoice_api_id) !!}
						{!! Form::submit('Create Sale', ['class' => 'btn btn-primary btn-sm btn-block']) !!}

						{!! Form::close() !!}
					</td>
					<td>
						{!! BsForm::open(['route' => 'batches.deal-sheet-delete-offer']) !!}
						{!! BsForm::hidden('id', $offer->id) !!}
						{!! BsForm::submit('Delete', ['class' => 'btn btn-danger btn-sm btn-block']) !!}
						{!! BsForm::close() !!}
					</td>
				</tr>
			@endforeach
		</table>

	</div>

@endsection