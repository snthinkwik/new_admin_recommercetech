<?php
use App\Sale;
use App\Invoice;


$retailOrder=\App\EbayOrders::where('new_sale_id',$sale->id)->first();

$invoicing = app('App\Contracts\Invoicing');
if(Auth::user()->type === 'admin') {
    if(in_array($sale->invoice_status, [Invoice::STATUS_OPEN])) {
        $statuses = [Invoice::STATUS_PAID => ucfirst(Invoice::STATUS_PAID)];
    } elseif(in_array($sale->invoice_status, [Invoice::STATUS_PAID])) {
        $statuses = [Invoice::STATUS_DISPATCHED => ucfirst(Invoice::STATUS_DISPATCHED)];
    }
}
$couriers = Sale::getAvailableCouriersWithKeys();
$totalSalePrice = 0;
$totalPurchasePrice=0;
$totalProfit=0;
$totalTrueProfit=0;

$grossProfitPercentage = 0;

$totalGrossProfitPercentage = 0;
$totalExVatPrice=0;
$profitPercentage=0;
$trueProfitPercentage=0;
$estProfit=0;
$estNetProfitPre=0;
$totalAmount=0;


foreach($sale->stock as $item) {
    $totalPurchasePrice +=$item->purchase_price;
    $totalProfit +=$item->profit;
    $totalTrueProfit +=$item->true_profit;

    if($item->vat_type==="Standard"){
        $totalExVatPrice +=$item->total_price_ex_vat;
	}else{
    	$totalExVatPrice+=$item->sale_price;
	}

    $totalSalePrice += $item->sale_price;

	$estProfit+= $item->true_profit - $sale->platform_fee;

if($item->vat_type===\App\Stock::VAT_TYPE_STD){
	$totalAmount+=$item->total_price_ex_vat;
}else{
	$totalAmount+=$item->sale_price;
}
//	$totalAmount
//	if($item->vat_type===\App\Stock::VAT_TYPE_STD){
//		$estNetProfitPre+=number_format(($estProfit/$item->total_price_ex_vat)*100,2);
//	}
//	else{
//		$estNetProfitPre+= number_format(($estProfit/$item->sale_price)*100,2);
//	}

}

if($sale->delivery_charges){
    $totalSalePrice +=$sale->delivery_charges;

	if(count($sale->stock)){
        if($sale->stock[0]->vat_type==="Standard"){
            $totalExVatPrice +=$sale->delivery_charges;
        }
	}


    $totalProfit += $sale->delivery_charges;
    $totalTrueProfit += $sale->delivery_charges;
}

//$grossProfitPercentage = $totalSalePrice ? number_format($grossProfit / $totalSalePrice * 100, 2):0;
//$totalGrossProfitPercentage =$totalSalePrice ? number_format($totalGrossProfit / $totalSalePrice *100, 2):0;

if(isset($sale->stock[0]) && $sale->stock[0]['vat_type']==="Standard"){
    $profitPercentage=$totalExVatPrice ? number_format($totalProfit/$totalExVatPrice * 100,2):0;
    $trueProfitPercentage=$totalExVatPrice ? number_format($totalTrueProfit/$totalExVatPrice * 100,2):0;
}else{

    $profitPercentage=$totalSalePrice ?number_format($totalProfit/$totalSalePrice * 100,2):0;
    $trueProfitPercentage=$totalSalePrice ? number_format($totalTrueProfit/$totalSalePrice * 100,2):0;
}

$estNetProfitPre=$totalAmount>0 ? ($estProfit/$totalAmount)*100:0



?>

@extends('app')

@section('title', 'Sale details - ' . $sale->created_at->format('Y-m-d H:i'))

@section('content')
	<div class="container-fluid">
		@include('messages')
		<h4><b>Date:</b> {{ $sale->created_at->format('Y-m-d H:i') }}</h4>
		@if ($sale->tracking_number)
			<h4><b>Tracking number:</b> {{ $sale->tracking_number }} <a class="btn btn-default btn-sm" data-toggle="collapse" data-target="#update-tracking-form"><i class="fa fa-pencil"></i></a></h4>
		@endif
		@if($sale->courier)
			<h5><b>Courier:</b> {{ $sale->courier }}</h5>
		@endif
		@if($sale->tracking_number)
			{!! BsForm::model($sale, ['method' => 'post', 'route' => 'sales.update-tracking', 'class' => 'form-inline collapse', 'id' => 'update-tracking-form']) !!}
			{!! BsForm::hidden('id', $sale->id) !!}
			{!! BsForm::groupText('tracking_number', null, ['required' => 'required']) !!}
			{!! BsForm::groupSelect('courier', $couriers, null, ['required']) !!}
			{!! BsForm::groupSubmit('Update Tracking', ['class' => 'btn-block']) !!}
			<hr/>
			{!! BsForm::close() !!}
		@endif
		<h4><b>Items</b></h4>
		@if($sale->invoice_number && Auth::user()->type === 'admin' && count($sale->stock))
			{!! BsForm::open(['route' => 'sales.send-order-imeis']) !!}
			{!! BsForm::hidden('id', $sale->id) !!}
            <?php
            $email=isset($customer->email)?$customer->email:''
            ?>

			{!! BsForm::submit("Email IMEI's",
				['class' => 'btn btn-sm btn-default confirmed mb10',
				'data-toggle' => 'tooltip', 'title' => "Send Email with IMEI's", 'data-placement'=>'right',
				'data-confirm' => "Are you sure you want to send this email to\n  $email?"])
			!!}
			{!! BsForm::close() !!}
		@endif
		@if(count($sale->stock))
			@if (Auth::user()->type === 'user')
				@include('sales.item-list')
			@else

				@include('sales.item-list-table')
				@if($sale->stock->sum('purchase_price') > 0)
					<h5>ex Vat Carriage: {{ money_format(config('app.money_format'), $sale->delivery_charges)  }} </h5>
                    <?php $profit_value = number_format($sale->stock->sum('total_costs')-$sale->stock->sum('purchase_price')); ?>
					{{--<h5>Total Profit Ratio ({{ $sale->profit_amount_formatted }}): {{ $sale->profit_ratio }}</h5>--}}
					<h5>Total Sell Price: <span @if($totalSalePrice<0) class="text-danger" @endif > {{ money_format(config('app.money_format'), $totalSalePrice)  }}</span></h5>
					@if($totalExVatPrice>0)
						<h5>Sell Price Ex Vat : <span @if($totalExVatPrice<0) class="text-danger" @endif > {{ money_format(config('app.money_format'), $totalExVatPrice )  }}</span></h5>
					@endif
					<h5>Total Profit : <span @if($totalProfit<0) class="text-danger" @endif > {{ money_format(config('app.money_format'), $totalProfit)  }}</span></h5>
					<h5>Total Profit% : <span @if($profitPercentage<0) class="text-danger" @endif > {{  $profitPercentage."%" }}</span></h5>
					@if($sale->stock[0]['vat_type']!=="Standard")
						<h5>True Total Profit : <span @if($totalTrueProfit<0) class="text-danger" @endif > {{  money_format(config('app.money_format'), $totalTrueProfit)  }}</span></h5>
						<h5>True Total Profit% :<span @if($trueProfitPercentage<0) class="text-danger" @endif >  {{ $trueProfitPercentage."%" }}</span></h5>
					@endif
					<h5>Est Net Profit: <span @if($estProfit<0) class="text-danger" @endif> {{money_format(config('app.money_format'),$estProfit) }}</span></h5>
					<h5>Est Net Profit %: <span @if($estProfit<0) class="text-danger" @endif> {{ number_format($estNetProfitPre,2)  .'%' }}</span></h5>


				@endif
			@endif
		@elseif($sale->item_name && $sale->vat_type)
			@if(Auth::user()->type === 'admin')
				<h4>Custom Order</h4>
			@endif
			<div class="row">
				<div class="col-md-4">
					<table class="table table-striped table-hover">
						<tr>
							<th>Item</th>
							<td>{{ $sale->item_name }}</td>
						</tr>
						<tr>
							<th>Amount</th>
							<td>{{ $sale->amount_formatted }}</td>
						</tr>
					</table>
				</div>
			</div>
		@endif

		@if($sale->buyers_ref)
			<h5><b>Buyers Ref:</b> {{ $sale->buyers_ref }}</h5>
		@endif

		@if($sale->other_recycler)
			<h4><b>Recycler:</b> {{ $sale->other_recycler }}</h4>
			@if($sale->account_name)
				<h5><b>Account Name:</b> {{ $sale->account_name }}</h5>
			@endif

			@if(Auth::user()->type === 'admin')
				<div class="row">
					<div class="col-md-4">
						<a class="btn btn-default btn-block" data-toggle="collapse" data-target="#other-change-recycler">Change Recycler</a>
						<div class="panel panel-default collapse" id="other-change-recycler">
							<div class="panel-body">
								{!! BsForm::open(['method' => 'post', 'route' => 'sales.other-change-recycler']) !!}
								{!! Form::hidden('id', $sale->id) !!}
								{!! Form::label('recycler', 'Recycler') !!}
								{!! BsForm::select('recycler', ['Cex' => 'CeX', 'Music Magpie' => 'Music Magpie', 'Envirofone' => 'Envirofone', 'eBay' => 'eBay','Other' => 'Other']) !!}
								{!! Form::label('other_recycler', "Recycler->Other (won't work if not Other selected)") !!}
								{!! BsForm::text('other_recycler', null, ['placeholder' => 'Other Recycler']) !!}
								{!! BsForm::submit('change', ['class' => 'mt5 btn-block']) !!}
								{!! BsForm::close() !!}
							</div>
						</div>
					</div>
				</div>
			@endif

		@else
			@if($customer)
				<span style="font-size: 20px;">Customer</span><br>
					@if(Auth::user()->type == 'admin')
						<a href="{{ route('admin.users.single', ['id' => $sale->user_id]) }}">{{ $customer->full_name }}</a>
					@else
						{{ $customer->full_name }}
					@endif

				<p>Email: {{ $customer->email }}</p>
				<p>Phone: {{ $customer->phone ? : $sale->user->phone }}</p>
				@if(Auth::user()->type === 'admin')
					@foreach (['billing_address', 'shipping_address'] as $addressType)
						@if ($customer->$addressType)
							<h5>{{ ucwords(str_replace('_', ' ', $addressType)) }}:</h5>
                            <?php $address = $customer->$addressType ?>
							<p>
								@if ($customer->full_name)    {{ $customer->full_name }}    <br> @endif
								@if ($address->line1)    {{ $address->line1 }}    <br> @endif
								@if ($address->line2)    {{ $address->line2 }}    <br> @endif
								@if ($address->city)     {{ $address->city }}     <br> @endif
								@if ($address->country)  {{ $address->country }}  <br> @endif
								@if ($address->postcode) {{ $address->postcode }} <br> @endif
								@if(!is_null($retailOrder))<b>Tel:</b> {{$retailOrder->billing_phone_number}}<br> @endif
								@if(!is_null($retailOrder))<b>Email:</b> {{$retailOrder->buyer_email}}<br> @endif
							</p>
						@endif
					@endforeach
				@endif
			@else
				<b class="text-danger">Error - No Customer</b>
			@endif
		@endif

		@if($sale->batch)
			<p>Batch: <a href="{{ route('batches.single', ['id' => $sale->batch_id]) }}">#{{ $sale->batch->id }}</a></p>
		@endif

		@if($sale->customer_first_name || $sale->customer_email || $sale->customer_phone)
			<p><b>Customer Detail:</b></p>
			<p>
				Name: {{($sale->customer_first_name) ? $sale->customer_first_name : '-'}}<br/>
				Email: {{($sale->customer_email) ? $sale->customer_email : '-'}}<br/>
				Phone number: {{($sale->customer_phone) ? $sale->customer_phone : '-'}}<br/>
			</p>
		@endif

		<p>
		@if($sale->other_recycler)
			<h4><b>Recyclers Order Number:</b> #{{ $sale->recyclers_order_number ? : '-' }}</h4>
		@else
			Invoice:
			@if ($sale->invoice_creation_status === 'success')
				@if ($sale->invoice_status === 'voided')
					<span class="text-danger small">({{ $sale->invoice_status_alt }})</span>
				@endif
				<a href="{{ route('sales.invoice', $sale->id) }}" target="blank">
					Invoice #{{ $sale->invoice_number }}{{!is_null($sale->invoice_doc_number)? "-".$sale->invoice_doc_number:'' }}
				</a>
				@else
				{{ $sale->invoice_creation_status_alt }}
				@endif
				@endif
				</p>
				@if(Auth::user()->type === 'admin')
					<div class="row mt5 mb10">
						<div class="col-md-4">
							@if($sale->payment_method)
								<p><b>Receipt:</b> <a target="_blank" href="{{ route('epos-sales.receipt', ['id' => $sale->id]) }}">Receipt</a></p>
							@endif
							@if(isset($sale->created_by_user))
								@if($sale->created_by && $sale->created_by_user->type !== "user")
									<b>Created By:</b> <a href="{{ route('admin.users.single', ['id' => $sale->created_by_user->id]) }}" target="_blank">{{ $sale->created_by_user->full_name }}</a>
								@endif
							@endif

							@if(!in_array($sale->payment_method,Sale::getAvailablePaymentMethods()) && !in_array($sale->invoice_status,[Invoice::STATUS_OPEN, Invoice::STATUS_PAID, Invoice::STATUS_READY_FOR_DISPATCH, Invoice::STATUS_DISPATCHED]))
								<h5><b>Status:</b> {{ ucfirst($sale->invoice_status_alt) }}</h5>
							@endif
							@if(in_array($sale->invoice_status, [Invoice::STATUS_OPEN, Invoice::STATUS_PAID]) && isset($statuses))
								<a class="btn btn-default btn-block" data-toggle="collapse" data-target="#single-change-status">Change Status</a>
								<div class="panel panel-default collapse" id="single-change-status">
									<div class="panel-body">
										{!! BsForm::open(['method' => 'post', 'route' => 'sales.single-change-status']) !!}
										{!! BsForm::hidden('id', $sale->id) !!}
										{!! BsForm::select('status', $statuses) !!}
										{!! BsForm::submit('Update Status', ['class' => 'btn-block']) !!}
										{!! BsForm::close() !!}
									</div>
								</div>
							@endif
							@if($sale->invoice_status == Invoice::STATUS_OPEN)
								{!! BsForm::open(['method' => 'post', 'route' => 'sales.re-create-invoice', 'class' => 'mt10']) !!}
								{!! BsForm::hidden('id', $sale->id) !!}
								{!! BsForm::submit('Re-Create Invoice', ['class' => 'btn-block confirmed', 'data-confirm' => 'Are you sure you want to Re-Create Invoice?']) !!}
								{!! BsForm::close() !!}
							@endif
							@if($sale->tracking_number || $sale->courier)
								<h5><b>Tracking Number: </b> {{ $sale->tracking_number }}</h5>
								@if($sale->courier)
									<h5><b>Courier: </b> {{ $sale->courier }}</h5>
								@endif
							@elseif(in_array($sale->invoice_status, [Invoice::STATUS_PAID, Invoice::STATUS_READY_FOR_DISPATCH, Invoice::STATUS_DISPATCHED]))

								@if(!in_array($sale->payment_method,Sale::getAvailablePaymentMethods()) && !in_array($sale->invoice_status,[Invoice::STATUS_OPEN, Invoice::STATUS_PAID, Invoice::STATUS_READY_FOR_DISPATCH, Invoice::STATUS_DISPATCHED]))
									<a class="btn btn-default btn-sm btn-block mt-5" data-toggle="collapse" data-target="#single-tracking-number">
										Add Tracking Number
									</a>
									<div class="panel panel-default collapse" id="single-tracking-number">
										{!! BsForm::open(['route' => 'sales.single-tracking-number']) !!}
										{!! BsForm::hidden('id', $sale->id) !!}
										{!! BsForm::text('number', null, ['placeholder' => 'Tracking number', 'required']) !!}
										{!! BsForm::select('courier', $couriers, Sale::COURIER_ROYAL_MAIL) !!}
										{!! BsForm::submit('Save tracking number', ['class' => 'btn-block']) !!}
										{!! BsForm::close() !!}
									</div>
								@endif

							@endif
						</div>
					</div>
				@endif
				@if (Auth::user()->canVoidSale($sale) && $sale->invoice_status != Invoice::STATUS_DISPATCHED)
					{!! Form::open(['route' => 'sales.cancel', 'class' => 'cancel-sale']) !!}
					{!! Form::hidden('id', $sale->id) !!}
					{!! BsForm::groupSubmit('Cancel', ['class' => 'btn-danger']) !!}
					{!! Form::close() !!}
				@endif
				@if (Auth::user()->canDeleteSale($sale))
					<p><a href="#delete-sale" class="btn btn-danger" data-toggle="collapse">Delete</a></p>
					{!! Form::open(['route' => 'sales.delete', 'id' => 'delete-sale', 'class' => 'delete-sale collapse']) !!}
					<p class="text-danger">
						Are you sure you want to delete this sale? This functionality is primarily intended for deleting test sales.
					</p>
					{!! Form::hidden('id', $sale->id) !!}
					<a href="#delete-sale" class="btn btn-default" data-toggle="collapse">Cancel</a>
					{!! BsForm::submit('Yes, delete', ['class' => 'btn-danger']) !!}
					{!! Form::close() !!}
				@endif

				@if($sale->sale_logs()->first())
					<hr/>
					<div class="panel panel-default">
						<div class="panel-heading">Logs</div>
						<div class="panel-body">
							<table class="table table-hover table-bordered">
								<thead>
								<tr>
									<th>User</th>
									<th>Content</th>
									<th>Date</th>
								</tr>
								</thead>
								<tbody>
								@foreach($sale->sale_logs()->orderBy('id', 'desc')->get() as $log)
									<tr>
										<td>@if($log->user) <a href="{{ route('admin.users.single', ['id' => $log->user->id]) }}">{{ $log->user->full_name }}</a> @else - @endif</td>
										<td>{!! $log->content !!}</td>
										<td>{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
									</tr>
								@endforeach
								</tbody>
							</table>
						</div>
					</div>
				@endif
	</div>
@endsection
