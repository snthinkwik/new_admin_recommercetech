<?php

$fields = ['name', 'capacity', 'grade', 'network', 'condition', 'colour', 'lcd_status', 'average_purchase_price', 'average_sale_price'];
$fields = array_combine($fields, $fields);
$fields = ['' => 'Leave Empty or Select'] + $fields;
$count = count($fields);
$n=0;
?>
@extends('app')

@section('title', "eBay Copy to What's App")

@section('content')

	<div class="container">

		<h2>eBay & eBay & Shop Items</h2>

		<div class="row">
			<div class="col-md-4">
				Select Fields
				{!! BsForm::open(['method' => 'get', 'id' => 'ebay-whats-app-form']) !!}
					@for($n=1; $n<$count; $n++)
						<div class="form-group">
							<div class="input-group">
								<span class="input-group-addon">
									{{ $n }}
								</span>
								{!! BsForm::select('fields[]', $fields, null) !!}
							</div>
						</div>
					@endfor
					{!! BsForm::button('Get Items', ['class' => 'btn-block', 'id' => 'get-items']) !!}
				{!! BsForm::close() !!}
			</div>
			<div class="col-md-8">
				<a class="btn btn-default batch-summary-copy-button">Copy for What's App</a>
				<div id="ebay-whats-app-items-wrapper">
					@include('stock.ebay-whats-app-items-list')
				</div>
			</div>
		</div>

	</div>

@endsection