@extends('app')

@section('title', 'Accessories')

@section('content')

<style>
	textarea.form-control{
		height: 33px !important;
	}

</style>

	<div class="container">

		@include('messages')

		<h2>Accessories</h2>

		<a class="btn btn-primary" data-toggle="collapse" data-target="#add-accessories"><i class="fa fa-plus"></i> Add Accessories</a>
		
		<div class="panel panel-default collapse" id="add-accessories">
			<div class="panel-body">
				{!! BsForm::open(['method' => 'post','files' => 'true', 'route' => 'sales.accessories.create']) !!}
					<div class="row">
						<div class="col-md-12">
							{!! BsForm::groupText('name', null, ['required' => 'required']) !!}
						</div>
						<div class="col-md-12">
							{!! BsForm::groupText('sku', null) !!}
						</div>
						<div class="col-md-12">
							{!! BsForm::groupText('quantity', null, ['required' => 'required']) !!}
						</div>
						<div class="col-md-12">
							<label>Image</label>
							{!! BsForm::groupFile('image') !!}
						</div>

						<div class="col-md-12">
							{!! BsForm::groupSubmit('Add Accessory', ['class' => 'btn-block','id' => 'btn-add']) !!}
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
				{!! BsForm::text('term', Request::input('term'), ['placeholder' => 'name, SKU']) !!}
			</div>
		</div>
		
		{!! BsForm::close() !!}

		<div class="row">
			<div class="col-md-12">
				<div id="universal-table-wrapper">
					@include('sales.accessories-list')
				</div>

				<div id="universal-pagination-wrapper">
					{!! $salesAccessories->appends(Request::all())->render() !!}
				</div>
			</div>
		</div>

	</div>

	
@endsection