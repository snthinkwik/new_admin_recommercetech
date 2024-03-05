<?php
use Illuminate\Support\Facades\Request;
use App\Models\Invoice;
$keys = ['any' => 'Any', 'open_paid' => 'Open & Paid', 'open_paid_other_recycler' => 'Open, Paid & Other Recyclers'];
$invoiceKeys = Invoice::getAvailableStatusesWithKeys();
if(Auth::user()->type !== 'user'){
	$keys = array_merge($keys, ['other_recycler' => 'Other Recycler']);
}
else{
	unset($invoiceKeys[Invoice::STATUS_VOIDED]);
}
if(isset($_GET['status']))
{
	$default = $_GET['status'];
}
else
{
	$default = Auth::user()->type === 'user' ? 'any' : 'open_paid_other_recycler';
}

?>

{!! Form::open(['id' => 'universal-search-form', 'class' => 'spinner form-inline mb15', 'method' => 'get']) !!}
	<div class="form-group">
		{!! BsForm::groupSelect('status', $keys + $invoiceKeys, $default) !!}
	</div>
	<div class="form-group">
	  {!! BsForm::text('imei', Request::input('imei'), ['id' => 'item-search', 'placeholder' => 'Search by IMEI,Serial,RCT12345', 'size' => 30]) !!}
	</div>
	<div class="form-group">
		{!! BsForm::text('buyers_ref', Request::input('buyers_ref'), ['id' => 'item-search', 'placeholder' => 'Search by Buyers Ref']) !!}
	</div>
	<div class="form-group">
		{!! BsForm::text('invoice_number', Request::input('invoice_number'), ['placeholder' => 'Search by Invoice Number']) !!}
	</div>
	@if(Auth::user()->type === 'admin')
		<div class="form-group">
			{!! BsForm::text('name', Request::input('name'), ['id' => 'item-search', 'placeholder' => 'Search by Name']) !!}
		</div>
		<div class="form-group">
			{!! BsForm::text('postcode', Request::input('postcode'), ['id' => 'item-search', 'placeholder' => 'Search by Postcode']) !!}
		</div>
		<!-- <div class="btn-group" data-toggle="buttons">
			<label class="btn btn-default {{ Request::input('ebay') == "t" ? "active" : null}}">
				{!!
				BsForm::checkbox(
					'ebay',
					't',
					Request::input('ebay') == "t" ? true : false
				)
				!!}
				eBay Orders
			</label>
		</div> -->
	@endif
{!! Form::close() !!}
