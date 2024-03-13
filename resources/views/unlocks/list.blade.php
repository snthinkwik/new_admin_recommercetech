<?php
use App\Models\Stock;
use App\Models\Unlock;
$networks = Stock::getAdminUnlockableNetworks();
?>
<table class="table table-striped table-responsive small" id="unlocks-table">
	<tr>
		<th></th>
		<th>ID</th>
		<th>Created/Updated</th>
		<th>IMEI</th>
		<th>Stock</th>
		<th>3rd Party Ref</th>
		<th>User</th>
		<th>Source</th>
		<th>Status</th>
		<th>Network</th>
		<th>Description</th>
		<th>ETA</th>
		<th>Cost Added</th>
		<th>Report</th>
		<th></th>
		<th>Timer</th>
	</tr>
	@foreach($unlocks as $unlock)
		<tr>
			<td>
				{!! BsForm::checkbox('ids_to_retry[' . $unlock->id . ']', 0, null, [
					'data-toggle' => 'tooltip',
					'title' => 'Mark to retry',
					'data-placement' => 'top'
				]) !!}
			</td>
			<td>{{ $unlock->id }}</td>
			<td>
				{{ $unlock->created_at->format('Y-m-d H:i') }} <br>
				{{ $unlock->updated_at->format('Y-m-d H:i') }}
			</td>
			<td>{{ $unlock->imei }} {{--@if($unlock->has_retail_order) <span class="text-info">Retail Order</span> @endif--}}</td>
			@if($unlock->stock_id)
				<td><a href="{{ route('stock.single', ['id' => $unlock->stock_id]) }}">{{ $unlock->stock_item->our_ref }}</a></td>
				<td>{{ $unlock->stock_item->third_party_ref }}</td>
			@else
				<td colspan="2">
					Item Name: {{ $unlock->item_name }} <i data-toggle="collapse" data-target="#unlock-item-name-{{ $unlock->id }}" class="fa fa-pencil"></i>
					{!! BsForm::model($unlock, ['method' => 'post', 'route' => 'unlocks.update-item-name', 'class' => 'collapse', 'id' => 'unlock-item-name-'.$unlock->id]) !!}
					{!! BsForm::hidden('id', $unlock->id) !!}
					<div class="form-group">
						<div class="input-group">
							{!! BsForm::text('item_name') !!}
							<span class="input-group-btn">{!! BsForm::button('<i class="fa fa-check"></i>', ['type' => 'submit']) !!}</span>
						</div>
					</div>
					{!! BsForm::close() !!}
				</td>
			@endif
			<td>
				@if ($unlock->user)
					{!! link_to_route('admin.users.single', $unlock->user->full_name, $unlock->user, ['target' => '_blank']) !!}
				@endif
			</td>
			<td>
				{{ !is_null($unlock->orders) ? 'own stock' : 'direct' }}
			</td>
			<td class="{{ $unlock->status_text_class }}">{{ $unlock->status }}</td>
			<td>{{ $unlock->network }}</td>
			<td>{{ $unlock->status_description }}</td>
			<td>{{ $unlock->eta->format('D jS F Y') }}</td>
			<td>{{ $unlock->cost_added_formatted }}</td>
			<td>@if($unlock->imei_report)<i class="fa fa-info" data-toggle="popover" data-html="true" title="Report"  data-trigger="hover" data-content="{!! $unlock->imei_report->report !!}"></i>@endif</td>
			<td>
				@if ($unlock->status != Unlock::STATUS_UNLOCKED)
					{!! BsForm::open(['route' => 'unlocks.mark-unlocked', 'class' => 'ib']) !!}
						{!! BsForm::hidden('id', $unlock->id) !!}
						{!! BsForm::submit('Mark as unlocked', ['class' => 'btn btn-default btn-sm confirmed']) !!}
					{!! BsForm::close() !!}
				@endif
				@if ($unlock->status === Unlock::STATUS_FAILED || ($unlock->status === Unlock::STATUS_NEW && $unlock->network == "Unknown") || $unlock->network == "Vodafone")
					{!! BsForm::open(['route' => 'unlocks.retry', 'class' => 'form-inline ib']) !!}
						{!! BsForm::hidden('id', $unlock->id) !!}
						{!! BsForm::select('network', array_combine($networks, $networks), $unlock->network, ['class' => 'input-sm']) !!}
						{!! BsForm::submit('Retry', ['class' => 'btn btn-default btn-sm confirmed']) !!}
					{!! BsForm::close() !!}
				@endif
				@if ($unlock->status != Unlock::STATUS_FAILED)
					{!! BsForm::open(['route' => 'unlocks.fail', 'class' => 'ib']) !!}
						{!! BsForm::hidden('id', $unlock->id) !!}
						{!! BsForm::submit('Fail', ['class' => 'btn btn-default btn-sm fail']) !!}
					{!! BsForm::close() !!}
				@endif
			</td>
			<td>{{ $unlock->timer }}</td>
			<td>
				@if($unlock->status == Unlock::STATUS_NEW && $unlock->stock_item)
					{!! $unlock->stock_item->getUnlockMapping($unlock->network) ? "<i class='fa fa-check' data-toggle='tooltip' title='unlock mapping found'></i>" : "<i class='fa fa-exclamation-triangle text-danger' data-toggle='tooltip' title='unlock mapping not found'></i>" !!}
				@endif
			</td>
		</tr>
	@endforeach
</table>
