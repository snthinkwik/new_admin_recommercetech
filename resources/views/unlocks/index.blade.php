<?php
use App\Models\Stock;
use App\Models\Unlock;
$networks = array_combine(Stock::getAdminUnlockableNetworks(),Stock::getAdminUnlockableNetworks());
$networks = ['' => 'Please Select'] + $networks + ['Vodafone Special' => 'Vodafone Special'];
?>
@extends('app')

@section('title', "Unlocks")

@section('content')

	<div class="container">
		@include('messages')

		{{--<p class="mb15">{!! link_to_route('unlocks.add', "Add unlocks") !!}</p>--}}

		@if(count($unlocks)==0)
			<p>Nothing to display.</p>
		@else

			<div class="row">
				<div class="col-md-4">
					<div class="form-group" id="bulk-retry-form">
						<div class="input-group">
							<span class="input-group-addon">Network</span>
							{!! BsForm::select('network', $networks, null, ['required' => 'required']) !!}
							<span class="input-group-btn">{!! BsForm::submit('Bulk Retry', ['id' => 'bulk-retry-button']) !!}</span>
						</div>
					</div>
				</div>
			</div>

			@include('unlocks.search-form')

			<div id="unlocks-table-wrapper">
				@include('unlocks.list')
			</div>

			<div id="unlocks-pagination-wrapper">
				{!! $unlocks->render() !!}
			</div>
		@endif
	</div>

	@include('unlocks.fail-reason-modal')

@endsection
