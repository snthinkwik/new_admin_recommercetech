<?php
use App\Models\Supplier;
use App\Models\Stock;
$suppliers = ['' => 'None'] + Supplier::get()->pluck('name', 'id')->toArray();
$ordersList = Stock::where('purchase_order_number', '<>', '')->orderBy('purchase_date','desc')->groupBy('purchase_order_number')->get();
$purchaseOrderNumber=[];
//foreach ($orders as $order){
//	array_push($purchaseOrderNumber,$order->purchase_order_number);
//}


$categoryList=[];
foreach ($ordersList as $key=>$order){
	$purchaseOrderNumber[$order->purchase_order_number]=$order->purchase_order_number;
}
?>
@extends('app')

@section('title', 'Purchase order stats')

@section('content')

	<div class="container-fluid">
		<div class="mx-xl-5">
		<h1>Purchase orders all</h1>
			{!! BsForm::open(['method' => 'get', 'id' => 'universal-search-form','class' => 'spinner form-inline mb10 mt10']) !!}
			<div class="form-group">
				<div class="input-group">
					<span class="input-group-addon">Supplier</span>
					{!! BsForm::select('supplier_id',$suppliers ,Request::input('supplier_id'), ['placeholder' => 'Select Supplier']) !!}
				</div>
				<div class="input-group">
					<span class="input-group-addon">Supplier</span>
				<input type="date" name="supplier_start" required> to <input type="date" name="supplier_end" required>
				</div>

				<div class="input-group">
					<span class="input-group-addon">Vat Type</span>
					{!! BsForm::select('vat_type',[''=>'All','Margin'=>'Margin','Standard'=>'Standard'] ,Request::input('vat_type'), ['placeholder' => 'Select Vat Type']) !!}
				</div>
				<div class="input-group">
					<span class="input-group-addon">Items Unsold</span>
					{!! BsForm::select('items_unsold',[""=>"All",'Yes'=>'Yes',"No"=>"No"] ,Request::input('items_unsold'), ['placeholder' => '']) !!}
				</div>
				<div class="input-group">
					<span class="input-group-addon">Purchase OrderNumber</span>
					{!! BsForm::select('purchase_order_number',[""=>'All',$purchaseOrderNumber] ,Request::input('purchase_order_number'), ['class'=>'purchase_order_number','placeholder' => '']) !!}
				</div>
			</div>
			{!! BsForm::close() !!}

			<form method="get" action="{{route('stock.purchase-order.csv')}}">

				<div class="input-group d-flex pt-4 pb-4" style="width: 600px">
{{--					{!! BsForm::select('supplier_id',['all'=>'All']+$suppliers ,Request::input('supplier_id'), ['required'=>true,'placeholder' => 'Select Supplier']) !!}--}}

					<div class="form-group mt-2">
						<div class="d-flex  input-group">
							<div class="input-group-addon"></div>
							<input type="date" name="start" required> to <input type="date" name="end" required>
						</div>
					</div>


					<div class="input-group-append">
						<input  class="btn btn-success" type="submit" value="CSV Export">
					</div>
				</div>
			</form>




		<div class="table table-striped table-hover stock table-h-sticky">
		<table class="table">
			<thead>
				<tr id="universal-sort-row">
					<th class="sort-column" data-name="purchase_order_number">PO Number</th>
					<th class="sort-column" data-name="supplier">Supplier</th>
					<th class="sort-column" data-name="purchase_date">Date</th>
					<th class="sort-column" data-name="items">No. Items</th>
					<th class="sort-column" data-name="sales_price">Sales Price</th>
					<th class="sort-column" data-name="total_sales_price">Sales Price (ex VAT - VATable)</th>
					<th class="sort-column" data-name="total_purchase_price" data-toggle="tooltip" title="Purchase Price" data-container="body">Device Purchase Cost</th>
					<th class="sort-column" data-name="total_purchase_price" data-toggle="tooltip" title="Purchase Price + Unlock + Part cost+Repair cost" data-container="body">Total Purchase Cost</th>
					<th class="sort-column" data-name="vat_type" data-toggle="tooltip" title="Vat Type" data-container="body">Vat Type</th>

					<th class="sort-column" data-name="items_sold">Items Sold</th>

					<th class="sort-column" data-name="items_returned">Items Returned</th>
					<th class="sort-column" data-name="total_items_returned_value" data-toggle="tooltip" title="Purchase Price + Unlock and Part cost of Items Returned" data-container="body">Value of Items Returned</th>
					<th class="sort-column" data-name="net_purchase_price">Net Purchase Price</th>
					<th class="sort-column" data-name="profit" data-toggle="tooltip" title="Sales Price - Purchase Price" data-container="body">Profit</th>
                    <th>Vat Margin</th>
					<th>True Profit </th>
					<th class="sort-column" data-name="profit_ratio" data-toggle="tooltip" title="Purchase Price / Sales Price * 100%" data-container="body">Profit Ratio%</th>
					<th>Seller Fees</th>
					<th>Est Net Profit</th>
					<th>Est Net Profit %</th>
					<th class="sort-column" data-name="items_to_sell">Items In Stock</th>
					<th class="sort-column" data-name="sales_price_in_stock" >Est Sales Price for unsold</th>


{{--			--}}

					<th class="sort-column" data-name="items_in_repair">Qty Repaired</th>
					<th class="sort-column" data-name="repair_cost">Total Repair Cost</th>
					<th class="sort-column" data-name="qty_unlocked">Qty Unlocked</th>
					<th class="sort-column" data-name="unlocking_cost">Total Unlocking Cost</th>
					<th>Qty sold B2B</th>
					<th>Qty Sold Retail</th>
					<th>Lost/Deleted</th>


					<th class="text-center"><i class="fa fa-globe"></i></th>
					<th>Details</th>
				</tr>
			</thead>
			<tbody id="universal-table-wrapper">
			   @include('stock.purchase-orders-all-list')
			</tbody>
		</table>
			<div id="universal-pagination-wrapper">{!! $orders->appends(Request::all())->render() !!}</div>
		</div>
	</div>
	</div>

@endsection
