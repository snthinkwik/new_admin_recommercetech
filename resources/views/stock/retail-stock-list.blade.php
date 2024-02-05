<p>
	No. Items: {{ $items->count() }}<br/>
	No. Missing SKU: {{ $missingSku->count() }}<br/>
	Purchase Total: {{ money_format(config('app.money_format'), $items->sum('total_costs')) }}<br/>
	Sales Total: {{ money_format(config('app.money_format'), $items->sum('sale_price')) }}
</p>

@if (!count($items))
	<div class="alert alert-warning">Nothing to show.</div>
@else
	<a class="btn btn-default" href="{{ route('stock.retail-stock-export') }}"><i class="fa fa-download"></i> Export</a>

	<a class="btn btn-default" id="ready-for-sale-copy-button">Copy for What's App</a>
	<a class="btn btn-default" data-toggle="collapse" data-target="#view-sku-summary">View SKU Summary</a>
	<a class="btn btn-default" id="ready-for-sale-unlock-selected">Unlock Selected</a>
	<textarea id="ready-for-sale-textarea" style="height:0; width:0;">
@foreach($itemsSummary as $item)
{{ $item->quantity }}x {{ $item->name }} - {{ $item->capacity_formatted }} - {{ $item->grade }}
@endforeach
</textarea>
	<div class="collapse panel-body" id="copy-whats-app-preview">
		@foreach($itemsSummary as $item)
			{{ $item->quantity }}x {{ $item->name }} - {{ $item->capacity_formatted }} - {{ $item->grade }}<br/>
		@endforeach
	</div>
	<div class="collapse panel-body" id="view-sku-summary">
		@foreach($skuSummary as $sku)
			{{ $sku->quantity }}x {{ $sku->new_sku }}<br/>
		@endforeach
	</div>
	<table class="table table-hover table-bordered mt10">
		<tr>
			<th>Ref</th>
			<th>3rd party ref</th>
			<th>SKU</th>
			<th>Make</th>
			<th>Name</th>
			<th>Capacity</th>
			<th>Colour</th>
			<th>Condition</th>
			<th>Grade</th>
			<th>Network</th>
			<th>Status</th>
			<th>Purchase Price</th>
			<th>Sale Price</th>
			<th>Unlock</th>
		</tr>
		@foreach($items as $item)
			<tr>
				<td><a href="{{ route('stock.single', ['id' => $item->id]) }}">{{ $item->our_ref }}</a></td>
				<td>{{ $item->third_party_ref }}</td>
				<td>
					{{ $item->new_sku }} <i class="fa fa-pencil" data-toggle="collapse" data-target="#update-new-sku-{{ $item->id }}"></i>
					{!! BsForm::model($item, ['method' => 'post', 'route' => 'stock.update-new-sku', 'id' => 'update-new-sku-'.$item->id, 'class' => 'collapse']) !!}
						{!! BsForm::hidden('id', $item->id) !!}
						<div class="form-group">
							<div class="input-group input-group-sm">
								{!! BsForm::text('new_sku', null, ['required' => 'required']) !!}
								<span class="input-group-btn">{!! BsForm::button('<i class="fa fa-check"></i>', ['type' => 'submit']) !!}</span>
							</div>
						</div>
					{!! BsForm::close() !!}
				</td>
				<td>{{ $item->make }}</td>
				<td>{{ $item->name }}</td>
				<td>{{ $item->capacity_formatted }}</td>
				<td>{{ $item->colour }}</td>
				<td>{{ $item->condition }}</td>
				<td>{{ $item->grade }}</td>
				<td>{{ $item->network }}</td>
				<td>{{ $item->status }}</td>
				<td>{{ $item->total_costs_formatted }}</td>
				<td>{{ $item->sale_price_formatted }}</td>
				<td>
					@if(!$item->unlock_available_errors)
						{!! BsForm::checkbox('unlock_items[' . $item->id . ']', $item->id, null, [
							'data-toggle' => 'tooltip',
							'title' => 'Mark to Unlock',
							'data-placement' => 'right',
						]) !!}
					@else
						<input disabled type="checkbox" title="{{ $item->unlock_available_errors }}" data-toggle="tooltip" data-placement="right">
					@endif
				</td>
			</tr>
		@endforeach
	</table>
@endif