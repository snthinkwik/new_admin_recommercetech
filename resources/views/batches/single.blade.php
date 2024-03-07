<?php
use App\Stock;
use App\Batch;
use Carbon\Carbon;
$networks = Stock::getAdminUnlockableNetworks();
?>
@extends('app')

@section('title', 'Batch')

@section('nav-right')
	@if (Auth::user()->type !== 'user')
		<div class="navbar-form navbar-right pr0">
			<div class="btn-group">
				<a href="{{ route('batches.export', ['id' => $batch->id, 'option' => 'download']) }}" class="btn btn-default">Export XLS</a>
				<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
					<span class="caret"></span>
					<span class="sr-only">Toggle Dropdown</span>
				</button>
				<ul class="dropdown-menu">
					<li><a href="{{ route('batches.export', ['id' => $batch->id, 'option' => 'batch_imeis']) }}" class="btn btn-default">Export with IMEIs</a></li>
				</ul>
			</div>
		</div>
	@endif
@endsection

@section('content')
	<div class="container">

		<a class="btn btn-default" href="{{ route('batches') }}"><i class="fa fa-reply"></i> Back to list</a>

		@include('messages')

		<h3>Batch #{{ $batch->id }} {{ $batch->name }} <span class="badge">{{ count($batch->stock) }}</span></h3>

		<div class="row">
			<div class="col-md-6">
				<p>
					Status: {{ $batch->status }}<br/>
					Total Purchase Price: {{ money_format(config('app.money_format'), $batch->stock->sum('total_costs')) }}<br/>
					Total Sales Price: {{ money_format(config('app.money_format'), $batch->stock->sum('sale_price')) }}<br/>
					Batch Sales Price: {{ money_format(config('app.money_format'), $batch->sale_price) }}<br/>
					Batch End TIme: {{ $batch->end_time_formatted }} <i class="btn btn-xs btn-default fa fa-pencil" data-toggle="collapse" data-target="#batch-end-time"></i>
				</p>

				{!! BsForm::open(['method' => 'post', 'route' => 'batches.update', 'class' => 'form-inline collapse mb10', 'id' => 'batch-end-time']) !!}
				{!! BsForm::hidden('id', $batch->id) !!}
				<div class="form-group">
					<div class="input-group">
						<span class="input-group-addon">End Time</span>
						{!! BsForm::text('end_time', $batch->end_time_formatted ? $batch->end_time : Carbon::now(), ['class' => 'has-datetimepicker', 'required' => 'required']) !!}
						<span class="input-group-btn">
					{!! BsForm::button('<i class="fa fa-check"></i>', ['type' => 'submit', 'class' => 'btn btn-success']) !!}
				</span>
					</div>
				</div>
				{!! BsForm::close() !!}

				{!! BsForm::model($batch, ['method' => 'post', 'route' => 'batches.update', 'class' => 'mb10']) !!}
				{!! BsForm::hidden('id', $batch->id) !!}
				<div class="form-group">
					<div class="input-group">
						<span class="input-group-addon">Name</span>
						{!! BsForm::text('name') !!}
						<span class="input-group-btn">{!! BsForm::button('<i class="fa fa-check"></i>', ['type' => 'submit']) !!}</span>
					</div>
				</div>
				{!! BsForm::close() !!}

				{!! BsForm::model($batch, ['method' => 'post', 'route' => 'batches.update', 'class' => 'mb10']) !!}
				{!! BsForm::hidden('id', $batch->id) !!}
				<div class="form-group">
					<div class="input-group">
						<span class="input-group-addon">Batch Sales Price</span>
						{!! BsForm::number('sale_price', null, ['step' => 0.01]) !!}
						<span class="input-group-btn">{!! BsForm::button('<i class="fa fa-check"></i>', ['type' => 'submit']) !!}</span>
					</div>
				</div>
				{!! BsForm::close() !!}

				@if(!$batch->custom_name)
					{!! BsForm::open(['method' => 'post', 'route' => 'batches.update-notes','class' => 'mb10', 'id' => 'update-all-notes']) !!}
					{!! BsForm::hidden('id', $batch->id) !!}
					<div class="form-group">
						<div class="input-group">
							<span class="input-group-addon">Notes</span>
							{!! BsForm::text('notes') !!}
							<span class="input-group-btn">
							{!! BsForm::submit('Update Notes (All Items)', ['class' => 'confirmed', 'data-confirm' => 'All items in this batch will be updated']) !!}
						</span>
						</div>
					</div>
					{!! BsForm::close() !!}
				@endif
			</div>
			<div class="col-md-6">
				<a data-toggle="collapse" data-target="#batch-image" class="btn btn-default"><i class="fa fa-image"></i> Image</a>
				@if($batch->custom_name)
					<a data-toggle="collapse" data-target="#batch-file" class="btn btn-default"><i class="fa fa-file"></i> File</a>
					<a data-toggle="collapse" data-target="#batch-description" class="btn btn-default"><i class="fa fa-info-circle"></i> Description</a>
				@endif
				<div class="collapse" id="batch-image">
					<div class="panel panel-default">
						<div class="panel-body">
							@if($batch->photo)
								<img class="img img-responsive img-thumbnail" src="{{ $batch->photo_url }}"/>
							@endif
							{!! Form::open(['route' => 'batches.update', 'files' => 'true']) !!}
								{!! Form::hidden('id', $batch->id) !!}
								<label>Photo</label>
								{!! Form::file('image') !!}
								{!! Form::submit('Submit', ['class' => 'btn btn-block btn-default']) !!}
							{!! Form::close() !!}
						</div>
					</div>
				</div>

				@if($batch->custom_name)
					<div class="collapse" id="batch-file">
						<div class="panel panel-default">
							<div class="panel-body">
								@if($batch->file)
									<a href="{{ $batch->file_url }}" target="_blank" class="btn btn-default">{{ $batch->file }}</a>
								@endif
								{!! Form::open(['route' => 'batches.update', 'files' => 'true']) !!}
									{!! Form::hidden('id', $batch->id) !!}
									<label>File</label>
									{!! Form::file('file') !!}
									{!! Form::submit('Submit', ['class' => 'btn btn-block btn-default']) !!}
								{!! Form::close() !!}
							</div>
						</div>
					</div>
					<div class="collapse" id="batch-description">
						<div class="panel panel-default">
							<div class="panel-body">
								{!! BsForm::model($batch, ['method' => 'post', 'route' => 'batches.update']) !!}
									{!! BsForm::hidden('id', $batch->id) !!}
									{!! BsForm::textarea('description') !!}
									{!! BsForm::submit('Submit', ['class' => 'btn btn-block btn-default']) !!}
								{!! BsForm::close() !!}
							</div>
						</div>
					</div>
				@endif
			</div>
		</div>

		<div class="row mb10">
			<div class="col-md-12">
				<a class="btn btn-sm btn-default" href="{{ route('batches.deal-sheet', ['id' => $batch->id]) }}">Deal Sheet</a>
				<a class="btn btn-sm btn-default" href="{{ route('batches.overview', ['id' => $batch->id]) }}">Batch overview</a>
				<a class="btn btn-sm btn-default" href="{{ route('batches.single-summary', ['id' => $batch->id]) }}">View Summary</a>
			</div>
		</div>

		<div class="row mb10">
			<div class="col-md-12">
				@if(!$batch->custom_name)
					{!! Form::open(['method' => 'post', 'route' => 'unlocks.add-by-stock', 'class' => 'form-inline ib']) !!}
						{!! Form::hidden('batch', 'y') !!}
						@foreach($batch->stock as $item)
							{!! Form::hidden('ids[]', $item->id) !!}
						@endforeach
						{!! BsForm::submit('Unlock All', ['class' => 'confirmed btn-sm', 'data-confirm' => 'Are you sure you want to unlock all available to unlock items?']) !!}
					{!! Form::close() !!}
	
					{!! BsForm::open(['id' => 'batch-clear-notes-form', 'class' => 'form-inline ib', 'method' => 'post', 'route' => 'batches.clear-notes']) !!}
						{!! BsForm::hidden('id', $batch->id) !!}
						{!! BsForm::submit("Clear Notes",
							['class' => 'confirmed btn-sm',
							'data-toggle' => 'tooltip', 'title' => "Clear Notes", 'data-placement'=>'top',
							'data-confirm' => "Are you sure you want to clear items notes?"])
						!!}
					{!! BsForm::close() !!}
				@endif

				{!! BsForm::open(['route' => 'batches.send', 'class' => 'form-inline ib']) !!}
					{!! BsForm::hidden('id', $batch->id) !!}
					{!!	BsForm::submit('Send Batch', ['class' => 'btn-sm confirmed', 'data-confirm' => "Are you sure you want to send this batch?"]) !!}
				{!! BsForm::close() !!}

				@if(!$batch->custom_name)
					<a class="btn btn-sm btn-primary" data-toggle="collapse" data-target="#send-to-user">Send to User</a>
				@endif

				@if($batch->status == Batch::STATUS_FOR_SALE)
					<a class="btn btn-sm btn-primary" data-toggle="collapse" data-target="#createSale">Create sale</a>
				@endif

				@if(!$batch->custom_name)
					<div class="collapse mt10" id="send-to-user">
						{!! BsForm::open(['method' => 'post', 'route' => 'batches.send-to-user', 'class' => 'form-inline']) !!}
						{!! BsForm::hidden('id', $batch->id) !!}
						<div class="form-group">
							<div class="input-group">
								<span class="input-group-addon">User ID</span>
								{!! BsForm::text('user_id', null, ['required' => 'required', 'placeholder' => 'User ID'], ['label' => 'User ID']) !!}
								<span class="input-group-btn">
									{!! BsForm::button('<i class="fa fa-check"></i> Send to User', ['type' => 'submit', 'class' => 'btn btn-default confirmed', 'data-confirm' => 'Batch Will be sent to selected User']) !!}
								</span>
							</div>
						</div>
						{!! BsForm::close() !!}
					</div>
				@endif

				@if($batch->status == Batch::STATUS_FOR_SALE)
					<div class="collapse mt10" id="createSale">
						@if($batch->custom_name)
							{!! BsForm::open(['route' => 'sales.custom-order', 'class' => 'form-inline', 'method' => 'get']) !!}
						@else
							{!! BsForm::open(['route' => 'sales.summary-batch', 'class' => 'form-inline']) !!}
						@endif
							{!! BsForm::hidden('batch', $batch->id) !!}
							@foreach($batch->stock as $item)
								{!! BsForm::hidden('items[' . $item->id . ']') !!}
							@endforeach
							<div class="form-group">
								<div class="input-group">
									<span class="input-group-addon">&pound;</span>
									{!! BsForm::number('price', null, [ 'min' => 1, 'class' => 'form-control', 'placeholder' => 'Price']) !!}
									<span class="input-group-btn">
										{!! BsForm::button('<i class="fa fa-check"></i> Create Sale', ['type' => 'submit', 'class' => 'btn btn-default confirmed', 'data-confirm' => 'Sale will be created']) !!}
									</span>
								</div>
							</div>
						{!! BsForm::close() !!}
					</div>
				@endif
			</div>
		</div>

		@if(!$batch->custom_name)
			<div class="row">
				<div class="col-md-2">
					<a id="batch-unlock-selected" href="javascript:" class="btn btn-default">Unlock selected</a>
				</div>
				@if(isset($saleId))
					<div class="col-md-2">
						<a class="btn btn-sm btn-default btn-block" href="{{ route('sales.single', ['id' => $saleId ]) }}" target="_blank">View Sale #{{ $saleId }}</a>
					</div>
				@endif
				<div class="col-md-6">
					<div id="change-grade-wrapper" class="form-inline">
						<div class="input-group">
							<span class="input-group-addon">Grade</span>
							<span class="input-group-btn">
								<a id="change-grade-select-all" class="btn btn-default" value="none">Select All</a>
							</span>
							{!!
								BsForm::select(
									'grade',
									Stock::getAvailableGradesWithKeys(),
									Request::input('grade'),
									[
										'id' => 'change-grade-grade',
										'class' => 'form-control'
									]
								)
							!!}
							<span class="input-group-btn">
								<a id="change-grade-submit" class="btn btn-primary">Change</a>
							</span>
						</div>
					</div>
				</div>
			</div>
		@endif

		@if(!$batch->custom_name)
			<table class="table small table-hover table-striped table-responsive mt10">
				<tr>
					<th><i class="fa fa-wrench" data-toggle="tooltip" title="Change grade checkbox"></i></th>
					<th><i class="fa fa-key" data-toggle="tooltip" title="Unlock checkbox"></i></th>
					<th>Ref</th>
					<th>View Item</th>
					<th>Name</th>
					<th>Capacity</th>
					<th>Colour</th>
					<th>Condition</th>
					<th>Grade</th>
					<th>Network</th>
					<th>3rd party ref</th>
					<th>Sales price</th>
					<th>Purchase date</th>
					<th>Purchase price</th>
					<th>Status</th>
					@if($batch->status == Batch::STATUS_FOR_SALE)
						<th class="text-center"><i class="fa fa-remove" data-toggle="tooltip" title="Remove item from Batch" data-placement="right"></i></th>
					@endif
				</tr>
				@foreach($batch->stock as $item)
					<tr>
						<td class="change-grade-checkbox">
							<form>
								{!! BsForm::checkbox('ids_to_change_grade[' . $item->id . ']', 0, null, [
									'data-toggle' => 'tooltip',
									'title' => 'Mark to change grade',
									'data-placement' => 'top'
								]) !!}
							</form>
						</td>
						<td>
							<form>
								@if ($item->imei && !$item->unlock && in_array($item->network, $networks))
									{!! BsForm::checkbox('ids_to_unlock[' . $item->id . ']', 0, null, [
										'data-toggle' => 'tooltip',
										'title' => 'Mark to unlock',
										'data-placement' => 'top'
									]) !!}
								@else
									<input disabled
									   type="checkbox"
									   @if (!$item->imei)
										   title="No IMEI."
										   @elseif ($item->network === 'Unlocked')
										   title="Already unlocked"
										   @elseif ($item->unlock)
										   title="Unlock already in progress"
										   @elseif (!in_array($item->network, $networks))
										   title="This network can't be unlocked."
										   @endif
										   data-toggle="tooltip"
									>
								@endif
							</form>
						</td>
						<td>{{ $item->our_ref }}</td>
						<td>
							<a class="sku-link" href="{{ route('stock.single', ['id' => $item->id]) }}">
								View Item
							</a>
						</td>
						<td>{{ $item->name }}</td>
						<td>{{ $item->capacity_formatted }}</td>
						<td>{{ $item->colour }}</td>
						<td>{{ $item->condition }}</td>
						<td>{{ $item->grade }}</td>
						<td>
							{{ $item->network }}
							@if($item->vodafone_unable_to_unlock)
								<span
										class="label label-danger big ib"
										data-toggle="popover"
										data-trigger="hover"
										data-content="this Vodafone device cannot be unlocked by us."
								>
										<i class="fa fa-lock"></i>
									</span>
							@elseif ($item->free_unlock_eligible)
								<span
										class="label label-info big ib unlock"
										data-toggle="popover"
										title="Free unlock"
										data-trigger="hover"
										data-content="This device is eligible for a free unlock. Once payment has been received we will submit the IMEI for an unlock which can take between 24 to 48 hours."
								>
										<i class="fa fa-unlock-alt"></i>
									</span>
							@endif
						</td>
						<td>{{ $item->third_party_ref }}</td>
						<td>{{ $item->sale_price_formatted }}</td>
						<td>{{ $item->purchase_date ? $item->purchase_date->format('Y-m-d') : '' }}</td>
						<td>{{ $item->total_costs_formatted }}</td>
						<td>{{ $item->status }} @if($item->status == "Sold") <a target="_blank" href="{{ route('sales.invoice', ['id' => $item->sale_id]) }}">#{{ $item->sale_id }}</a> @endif</td>
						@if($batch->status == Batch::STATUS_FOR_SALE)
							<td>{!! BsForm::open(['route' => 'stock.remove-from-batch']) !!}
								{!! BsForm::hidden('stock', $item->id) !!}
								{!! BsForm::button('<i class="fa fa-remove"></i>',
									['type' => 'submit',
									'class' => 'btn btn-sm btn-default btn-block confirmed',
									'data-toggle' => 'tooltip', 'title' => "Remove from Batch", 'data-placement'=>'right',
									'data-confirm' => "Are you sure you want to delete this device from batch?"])
								!!}
								{!! BsForm::close() !!}</td>
						@endif
					</tr>
				@endforeach
			</table>
		@endif
	</div>
@endsection



