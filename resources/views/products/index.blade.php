<?php
use App\Models\Category;
$vatTypeList=[''=>'Select Vat Type','Margin'=>'Margin','Standard'=>'Standard'];
$non_Serialised=[''=>'Select Non Serialised','1'=>'Yes','0'=>'No'];
$category=Category::select('name')->get();
$categoryList=[];
foreach ($category as $key=>$category){
    $categoryList[$category['name']]=$category['name'];
}


?>

@extends('app')

@section('title', 'Products')

@section('content')

	<div class="container">

		<h2>Products</h2>

		@include('messages')


		<div class="row">
			<div class="col-md-8"><a class="btn btn-default" href="{{route('product.create')}}"><i class="fa fa-plus"></i> Create Product</a></div>

			<div class="col-md-2">
				<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#exampleModal">
					<i class="fa fa-upload" aria-hidden="true"></i>
					Import Csv File
				</button>

				<!-- Modal -->
				<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
					<div class="modal-dialog" role="document">
						<div class="modal-content">
							<div class="modal-header">
								<h5 class="modal-title" id="exampleModalLabel">Modal title</h5>
								<button type="button" class="close" data-dismiss="modal" aria-label="Close">
									<span aria-hidden="true">&times;</span>
								</button>
							</div>
							<div class="modal-body">
								{!! Form::open(['route' => 'product.import', 'files' => true ]) !!}
								<div class="form-group">
									{!! Form::file('csv', ['class'=>"form-control",'accept' => '.csv']) !!}
								</div>

							</div>
							<div class="modal-footer">
								<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
								{!! Form::submit('Import', ['class' => 'btn btn-primary']) !!}
							</div>

							{!! Form::close() !!}
						</div>
					</div>
				</div>

			</div>

			<div class="col-md-2">	<a href="{{route('product.export-data')}}" class="btn btn-primary"><i class="fa fa-download" aria-hidden="true"></i>
					Export Product</a></div>
		</div>

		{!! BsForm::open(['method' => 'get', 'id' => 'universal-search-form', 'class' => 'spinner form-inline mb10 mt10']) !!}
			<div class="form-group">
				<div class="input-group">
					<span class="input-group-addon">Search</span>
					{!! BsForm::text('term', Request::input('term'), ['placeholder' => 'Product Name, Make, Model']) !!}
				</div>
				<div class="input-group">
					<span class="input-group-addon">Vat Type</span>
					{!! BsForm::select('vat_type',$vatTypeList ,Request::input('vat_type'), ['placeholder' => 'Select Vat Type']) !!}
				</div>

				<div class="input-group">
					<span class="input-group-addon">Non Serialised</span>
					{!! BsForm::select('non_serialised',$non_Serialised ,Request::input('non_serialised'), ['placeholder' => 'Select Non Serialised']) !!}
				</div>
				<div class="input-group">
					<span class="input-group-addon">Category</span>
					{!! BsForm::select('category',[""=>"Select Category"]+$categoryList ,Request::input('category'), ['placeholder' => 'Select Category']) !!}
				</div>
			</div>
		{!! BsForm::close() !!}

		<div id="universal-table-wrapper">
			@include('products.list')
		</div>

		<div id="universal-pagination-wrapper">
			{!! $products->appends(Request::all())->render() !!}
		</div>

	</div>

@endsection
