<?php
use Illuminate\Support\Facades\Request;
?>
@extends('app')

@section('title', 'Customers with Balance')

@section('content')

	<div class="container">

		@include('messages')

		<h2>Customers with Balance</h2>

		<h5>Total: {{ money_format(config('app.money_format'), $customers->sum('balance_due')) }}</h5>

		<div class="row">
			<div class="col-md-12">

				{!! BsForm::open(['method' => 'get', 'id' => 'universal-search-form', 'class' => 'form-inline mb10']) !!}
					<div class="form-group">
						<div class="input-group">
							<span class="input-group-addon">Hidden?</span>
							{!! BsForm::select('hidden', ['' => 'All', 0 => 'Yes', 1 => 'No'], Request::input('hidden')) !!}
						</div>
					</div>
				{!! BsForm::close() !!}

				{!! BsForm::open(['method' => 'post', 'route' => 'admin.users.customers-with-balance-reminders', 'class' => 'form-inline ib']) !!}
				{!! BsForm::button('Send Reminders', ['id' => 'send-reminders-button']) !!}
				{!! BsForm::close() !!}

				{!! BsForm::open(['method' => 'post', 'route' => 'admin.users.customers-with-balance-hide', 'class' => 'form-inline ib']) !!}
				{!! BsForm::button('Hide', ['id' => 'balance-hide-button']) !!}
				{!! BsForm::close() !!}


				<div id="universal-table-wrapper" class="mt10">
					@include('admin.users.customers-with-balance-list')
				</div>

			</div>
		</div>

	</div>

@endsection