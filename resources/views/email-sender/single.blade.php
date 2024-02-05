<?php
use App\Email;
?>
@extends('app')

@section('title', "Email \"" . str_limit($email->subject, 50) . "\"")

@section('pre-scripts')
	<script src="https://cdnjs.cloudflare.com/ajax/libs/canvasjs/1.7.0/canvasjs.min.js"></script>
@endsection

@section('content')

	<div class="container">
		<p>{!! link_to_route('emails', 'Back to list', [], ['class' => 'btn btn-default']) !!}</p>
		<div class="row">
			<div class="col-md-6">
				@include('email-sender.form', ['edit' => false])
			</div>
			<div class="col-md-6">
				<p>Email preview:</p>
				<iframe id="email-preview"></iframe>

				<div class="panel panel-default">
					<div class="panel-heading">Delivery Summary</div>
					<div class="panel-body">
						<p class="text-muted">Based on emails tracking, no data means that it wasn't stored when this email has been sent</p>
						<table class="table table-bordered table-hover">
							<tr>
								<th>Sent</th>
								<td class="chart-data-total" data-count="{{ $data->total }}">{{ $data->total }}</td>
							</tr>
							<tr>
								<th>Delivered</th>
								<td class="chart-data-delivered" data-count="{{ $data->delivered_formatted }}"><a href="{{ route('emails.single-delivery-summary', ['id' => $email->id, 'type' => 'delivered']) }}">{{ $data->delivered }} ({{ $data->delivered_formatted }}%)</a></td>
							</tr>
							<tr>
								<th>Opened</th>
								<td class="chart-data-opened" data-count="{{ $data->opened_formatted }}"><a href="{{ route('emails.single-delivery-summary', ['id' => $email->id, 'type' => 'opened']) }}">{{ $data->opened }} ({{ $data->opened_formatted }}%)</a></td>
							</tr>
							<tr>
								<th>Clicked</th>
								<td class="chart-data-clicked" data-count="{{ $data->clicked_formatted }}"><a href="{{ route('emails.single-delivery-summary', ['id' => $email->id, 'type' => 'clicked']) }}">{{ $data->clicked }} ({{ $data->clicked_formatted }}%)</a></td>
							</tr>
							<tr>
								<th>Failed</th>
								<td class="chart-data-failed" data-count="{{ $data->failed_formatted }}"><a href="{{ route('emails.single-delivery-summary', ['id' => $email->id, 'type' => 'failed']) }}">{{ $data->failed }} ({{ $data->failed_formatted }}%)</a></td>
							</tr>
							<tr>
								<th>Marked as Spam</th>
								<td class="chart-data-spam" data-count="{{ $data->spam_formatted }}"><a href="{{ route('emails.single-delivery-summary', ['id' => $email->id, 'type' => 'spam']) }}">{{ $data->spam }}  ({{ $data->spam_formatted }}%)</a></td>
							</tr>
						</table>

						@if($data->total > 0)
							<div id="delivery-summary-chart" style="min-height: 500px"></div>
						@endif
					</div>
				</div>
			</div>
		</div>
		@if ($email->status === Email::STATUS_ERROR)
			<h2 class="text-error">Error info</h2>
			<div class="alert alert-danger">{!! nl2br(e($email->status_details)) !!}</div>
		@endif
	</div>

@endsection