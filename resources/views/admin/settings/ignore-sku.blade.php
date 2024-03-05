@extends('app')

@section('title', 'Ignore SKU')

@section('content')

	<div class="container">

		@include('admin.settings.nav')
		@include('messages')

		<h2>Ignore SKU</h2>

		<div class="row mb10">
			<div class="col-md-12">
				{!! BsForm::open(['method' => 'post', 'route' => 'admin.settings.ignore-sku-add']) !!}
				{!! BsForm::textarea(
					'sku_list',
					null,
					['placeholder' => 'One or more SKU, separated by new lines, spaces or commas...', 'ROWS' => 3],
					['label' => 'Enter SKU']
				) !!}
				{!! BsForm::groupSubmit('Add', ['class' => 'btn-sm btn-block']) !!}
				{!! BsForm::close() !!}
			</div>
		</div>

		@if(!count($skus))
			<div class="alert alert-info">Nothing Found</div>
		@else
			<table class="table table-hover table-bordered">
				<thead>
				<tr>
					<th class="col-xs-1">#</th>
					<th class="col-xs-9">SKU</th>
					<th class="col-xs-2">Delete</th>
				</tr>
				</thead>
				<tbody>
				@foreach($skus as $sku)
					<tr>
						<td>{{ $sku->id }}</td>
						<td>{{ $sku->sku }}</td>
						<td>
							{!! BsForm::open(['route' => 'admin.settings.ignore-sku-remove', 'method' => 'post']) !!}
							{!! BsForm::hidden('id', $sku->id) !!}
							{!! BsForm::submit('Delete',
								['type' => 'submit',
								'class' => 'btn btn-xs btn-block btn-danger confirmed',
								'data-toggle' => 'tooltip', 'title' => "Delete SKU", 'data-placement'=>'right',
								'data-confirm' => "Are you sure you want to delete this SKU?"])
							!!}
							{!! BsForm::close() !!}
						</td>
					</tr>
				@endforeach
				</tbody>
			</table>
		@endif

	</div>

@endsection