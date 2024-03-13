@extends('app')

@section('title', 'Suppliers')

@section('content')

	<div class="container">

		@include('messages')

		<h2>Suppliers</h2>

		<a class="btn btn-sm btn-default" data-toggle="collapse" data-target="#add-supplier"><i class="fa fa-plus"></i></a>
		<div class="panel panel-default collapse" id="add-supplier">
			<div class="panel-body">
				{!! BsForm::open(['method' => 'post', 'route' => 'suppliers.add']) !!}
					<div class="row">

						<div class="col-md-4">
							{!! BsForm::groupNumber('crm_id', null,[],['label' => 'CRM ID']) !!}
						</div>
						<div class="col-md-4">
							{!! BsForm::groupText('name', null, ['required' => 'required']) !!}
						</div>
						<div class="col-md-4">
							{!! BsForm::groupText('address_1', null) !!}
						</div>
						<div class="col-md-4">
							{!! BsForm::groupText('address_2') !!}
						</div>
						<div class="col-md-4">
							{!! BsForm::groupText('town', null) !!}
						</div>
						<div class="col-md-4">
							{!! BsForm::groupText('county', null) !!}
						</div>
						<div class="col-md-4">
							{!! BsForm::groupText('postcode', null) !!}
						</div>
						<div class="col-md-4">
							{!! BsForm::groupText('email_address', null) !!}
						</div>
						<div class="col-md-4">
							{!! BsForm::groupText('contact_name', null) !!}
						</div>
						<div class="col-md-4">
							{!! BsForm::groupText('returns_email_address', null) !!}
						</div>
						<div class="col-md-12">
							{!! BsForm::submit('Add', ['class' => 'btn btn-info btn-sm btn-block']) !!}
						</div>
					</div>
				{!! BsForm::close() !!}
			</div>
		</div>

		{!! BsForm::open(['id' => 'universal-search-form', 'class' => 'form-inline mt5 mb15', 'method' => 'get']) !!}
		<div class="form-group">
			<div class="input-group">
				<span class="input-group-addon">
					Search
				</span>
				{!! BsForm::text('term', Request::input('term'), ['id' => 'universal-search-term', 'placeholder' => 'Search...']) !!}
			</div>
		</div>
		{!! BsForm::close() !!}

		<div class="row">
			<div class="col-md-12">
				<div id="universal-table-wrapper">
					@include('suppliers.list')
				</div>
				<div id="universal-pagination-wrapper">
					{!! $suppliers->appends(Request::All())->render() !!}
				</div>
			</div>
		</div>

	</div>

@endsection