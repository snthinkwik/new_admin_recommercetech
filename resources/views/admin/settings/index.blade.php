<?php
use Carbon\Carbon;
?>
@extends('app')

@section('title', 'Site settings')

@section('content')

	<div class="container">
		@include('admin.settings.nav')
		@include('messages')

		<h1>Site settings</h1>
		{{--{!! BsForm::open(['route' => 'admin.settings.submit']) !!}

			<h3>Crons</h3>

			<h5>Back Market - Push Qty</h5>
			<div class="form-group">
				<div class="checkbox">
					<label>
						{!! BsForm::hidden('crons[back-market-update-retail-stock-quantities][enabled]', 0) !!}
						{!! BsForm::checkbox('crons[back-market-update-retail-stock-quantities][enabled]', 1, Setting::get('crons.back-market-update-retail-stock-quantities.enabled', true), ['data-toggle' => 'toggle', 'data-onstyle' => 'default']) !!}
					</label>
				</div>
			</div>--}}

			{{--@if (config('app.env') !== 'production')
				<h3>Mailing</h3>
				<div class="row">
					<div class="col-md-4">
						{!!
							BsForm::groupSelect(
								'mail_driver',
								['' => 'Please select', 'log' => 'Log', 'mailgun' => 'Mailgun'],
								config('mail.driver'),
								in_array(config('mail.driver'), ['log', 'mailgun']) ? [] : ['disabled']
							)
						!!}
					</div>
				</div>
			@endif--}}

			{{--{!! BsForm::groupSubmit('Submit') !!}
		{!! BsForm::close() !!}--}}

		<h1>Free Delivery</h1>
		{!! BsForm::open(['method' => 'post', 'route' => 'admin.settings.free-delivery', 'class' => 'form-inline']) !!}
		<div class="form-group">
			<div class="checkbox">
				<label>

					{!! BsForm::hidden('free_delivery', 0) !!}
					{!! BsForm::checkbox('free_delivery', 1, Setting::get('free_delivery', false), ['data-toggle' => 'toggle', 'data-onstyle' => 'default']) !!}
				</label>
			</div>
		</div>
		{!! BsForm::submit('Save') !!}
		{!! BsForm::close() !!}

		<h1>Stock clearing</h1>
		<div class="row">
			<div class="col-md-4">
				<p class="text-danger"><b>Truncate any records that are Inbound or In Stock</b></p>
				<a class="btn btn-block btn-danger" data-toggle="collapse" data-target="#clearStock">Clear Stock</a>
				<div class="panel panel-danger panel-body collapse" id="clearStock">
					<div class="alert alert-danger">
						<h5>Clearing stock will truncate any records that are <b>inbound</b> or <b>in stock</b></h5>
						<h5>Are you sure you want to delete them?</h5>
					</div>
					<a class="btn btn-danger btn-block btn-sm" href="{{ route('admin.settings.clear-stock') }}">Clear Stock</a>
				</div>
			</div>
		</div>
		<h1>Update Stock</h1>
		<div class="row">
			<div class="col-md-4">
				<p class="text-danger"><b>Update stock status to sold</b></p>
				<a class="btn btn-block btn-danger" data-toggle="collapse" data-target="#updateStock">Update Stock</a>


				<div class="panel panel-danger panel-body collapse" id="updateStock">
					<div class="alert alert-danger">
						<h5>Update status to sold If IMEI match from stock table with Custom Label in master ebay table.</h5>
						<h5>Are you sure you want update status?</h5>
					</div>
					<a class="btn btn-danger btn-block btn-sm" href="{{ route('admin.settings.update-stock') }}">Update Stock</a>

				</div>
			</div>
		</div>

		{{--MODALS with forms--}}
		<div id="batches-run-once-modal" class="modal fade" role="dialog">
			<div class="modal-dialog modal-sm">
				<div class="modal-content">
					<div class="modal-header text-center">
						Batch Cron
					</div>
					<div class="modal-body">
						{!! BsForm::open(['route' => 'admin.settings.run-cron', 'method' => 'post', 'class' => '']) !!}
						{!! BsForm::hidden('cron', "batch-create") !!}
						{!! BsForm::button('Run Once',
							['type' => 'submit',
							'class' => 'btn btn-default btn-block confirmed',
							'data-toggle' => 'tooltip', 'title' => "Run Batch script once", 'data-placement'=>'bottom',
							'data-confirm' => "Are you sure you want to run Batch script once?"])
						!!}
						{!! BsForm::close() !!}
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-block btn-sm btn-default" data-dismiss="modal">Close</button>
					</div>
				</div>
			</div>
		</div>

		<div id="your-stock-run-once-modal" class="modal fade" role="dialog">
			<div class="modal-dialog modal-sm">
				<div class="modal-content">
					<div class="modal-header text-center">
						Your Stock Cron
					</div>
					<div class="modal-body">
						{!! BsForm::open(['route' => 'admin.settings.run-cron', 'method' => 'post', 'class' => '']) !!}
						{!! BsForm::hidden('cron', "your-stock") !!}
						{!! BsForm::button('Run Once',
							['type' => 'submit',
							'class' => 'btn btn-default btn-block confirmed',
							'data-toggle' => 'tooltip', 'title' => "Run Your Stock script once", 'data-placement'=>'bottom',
							'data-confirm' => "Are you sure you want to run Your Stock script once?"])
						!!}
						{!! BsForm::close() !!}
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-block btn-sm btn-default" data-dismiss="modal">Close</button>
					</div>
				</div>
			</div>
		</div>

		<div id="your-stock-unregistered-run-once-modal" class="modal fade" role="dialog">
			<div class="modal-dialog modal-sm">
				<div class="modal-content">
					<div class="modal-header text-center">
						Your Stock Unregistered Cron
					</div>
					<div class="modal-body">
						{!! BsForm::open(['route' => 'admin.settings.run-cron', 'method' => 'post', 'class' => '']) !!}
						{!! BsForm::hidden('cron', "your-stock-unregistered") !!}
						{!! BsForm::button('Run Once',
							['type' => 'submit',
							'class' => 'btn btn-default btn-block confirmed',
							'data-toggle' => 'tooltip', 'title' => "Run Your Stock Unregistered script once", 'data-placement'=>'bottom',
							'data-confirm' => "Are you sure you want to run Your Stock Unregistered script once?"])
						!!}
						{!! BsForm::close() !!}
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-block btn-sm btn-default" data-dismiss="modal">Close</button>
					</div>
				</div>
			</div>
		</div>
		{{--END Modals with forms--}}
	</div>

@endsection
