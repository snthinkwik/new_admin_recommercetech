<?php
use App\EmailWebhook;
$types = ['' => 'All'] + EmailWebhook::getAvailableTypesWithKeys();
?>
@extends('app')

@section('title', 'Email Delivery Summary')

@section('content')

	<div class="container">
		@include('messages')
		<a class="btn btn-default" href="{{ route('emails.single', ['id' => $email->id]) }}">Back to Email</a>

		{!! BsForm::open(['method' => 'get', 'id' => 'universal-search-form', 'class' => 'form-inline mb5 mt5']) !!}
			{!! BsForm::groupSelect('type', $types, Request::input('type')) !!}
		{!! BsForm::close() !!}

		<div id="universal-table-wrapper">
			@include('email-sender.single-delivery-summary-list')
		</div>

		<div id="universal-pagination-wrapper">
			{!! $events->appends(Request::all())->render() !!}
		</div>
	</div>

@endsection