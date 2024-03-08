<?php
use App\User;
$adminTypes = ['' => ' - '] + User::getAvailableAdminTypesWithKeys();
?>
@extends('app')

@section('title', 'Recommercetech Users')

@section('content')

	<div class="container">

		<h2>Recommercetech Users</h2>

		@include('messages')

		<div class="row">

			<div class="col-md-12">

				<a class="btn btn-sm btn-default" data-toggle="collapse" data-target="#create-admin-form"><i class="fa fa-plus"></i></a>
				<div class="panel panel-default collapse" id="create-admin-form">
					<div class="panel-body">
						{!! BsForm::open(['method' => 'post', 'route' => 'admin.users.create-admin']) !!}
							<div class="row">
								<div class="col-md-6">
									{!! BsForm::groupText('email', null, ['required' => 'required']) !!}
									{!! BsForm::groupText('first_name', null, ['required' => 'required']) !!}
									{!! BsForm::groupText('last_name', null, ['required' => 'required']) !!}
								</div>
								<div class="col-md-6">
									{!! BsForm::groupText('password', null, ['required' => 'required']) !!}
									{!! BsForm::groupSelect('admin_type', $adminTypes, null, ['required' => 'required']) !!}
									{!! BsForm::groupSubmit('save', ['class' => 'btn-block']) !!}
								</div>
							</div>
						{!! BsForm::close() !!}
					</div>
				</div>

				{!! BsForm::open(['id' => 'universal-search-form', 'class' => 'form-inline mb10 mt10']) !!}
					<div class="form-group">
						<div class="input-group">
							<span class="input-group-addon">Admin Type</span>
							{!! BsForm::select('admin_type', $adminTypes, Request::input('admin_type')) !!}
						</div>
					</div>
				{!! BsForm::close() !!}

				<div id="universal-table-wrapper">
					@include('admin.users.recommercetech-users-list')
				</div>

				<div id="universal-pagination-wrapper">
					{!! $users->appends(Request::all())->render() !!}
				</div>

			</div>

		</div>

	</div>

@endsection