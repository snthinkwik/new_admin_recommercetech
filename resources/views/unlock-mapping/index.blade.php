<?php
use App\Models\UnlockMapping;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;
$networks = ['' => 'Please Select'] + array_combine(UnlockMapping::getAvailableNetworks(), UnlockMapping::getAvailableNetworks());
$makes = ['' => ''] + Stock::select('make', DB::raw('count(*) as c'))->groupBy('make')->orderBy('c', 'desc')->pluck('make', 'make')->toArray();
$devices = ['' => ''] + Stock::select('name', DB::raw('count(*) as c'))->groupBy('name')->orderBy('c', 'desc')->pluck('name', 'name')->toArray();
?>
@extends('app')

@section('title', "Unlock Mapping")

@section('styles')
	<link href="{{ asset('css/select2.min.css') }}" rel="stylesheet">
	<style>
		.select2-selection--single {
			height: 34px !important;
			line-height: 14px !important;
			padding: 10px 24px 6px 12px;
			/**
			   * Adjust the single Select2's dropdown arrow button appearance.
			   */
		}
	</style>
@endsection

@section('scripts')
	<script src="{{ asset('js/select2.min.js') }}"></script>
	<script>
		$('#device-select2').select2();
		$('#make-select2').select2();
	</script>
@endsection

@section('content')

	<div class="container">

		<h2>Unlock Mapping</h2>

		@include('messages')

		<span class="text-info"><b>Device</b>: for example <i>iPhone X</i>. Empty = all items.</span>
		<br/>
		{!! BsForm::open(['method' => 'post', 'route' => 'unlock-mapping.add', 'class' => 'mb10']) !!}
		<div class="row">
			<div class="col-md-4">
				<div class="form-group">
					<div class="input-group">
						<span class="input-group-addon">Network</span>
						{!! BsForm::select('network', $networks, null, ['required' => 'required']) !!}
					</div>
				</div>
			</div>
			<div class="col-md-4">
				<div class="form-group">
					<div class="input-group">
						<span class="input-group-addon">Service ID</span>
						{!! BsForm::number('service_id', null, ['required' => 'required', 'min' => 1]) !!}
					</div>
				</div>
			</div>
			<div class="col-md-4">
				<div class="form-group">
					<div class="input-group input-group">
						<span class="input-group-addon">Make</span>
						{!! BsForm::select('make', $makes, null, ['id' => 'make-select2']) !!}
					</div>
				</div>
			</div>
			<div class="col-md-4">
				<div class="form-group">
					<div class="input-group input-group">
						<span class="input-group-addon">Device</span>
						{!! BsForm::select('model', $devices, null, ['id' => 'device-select2']) !!}
					</div>
				</div>
			</div>
			<div class="col-md-4">
				<div class="form-group">
					<div class="input-group input-group">
						<span class="input-group-addon">Cost</span>
						{!! BsForm::number('cost', 0, ['min' => 0]) !!}
					</div>
				</div>
			</div>
			<div class="col-md-4">
				<div class="form-group">
					<div class="input-group">
						<span class="input-group-btn">{!! BsForm::submit('Add', ['class' => 'btn-block']) !!}</span>
					</div>
				</div>
			</div>
		</div>
		{!! BsForm::close() !!}

		@include('unlock-mapping.list')

	</div>

@endsection
