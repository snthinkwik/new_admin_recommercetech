<?php
use App\Models\Invoice;
use App\Models\Stock;
use App\Models\Unlock\Pricing;
$apiParams = ['key' => Auth::user()->api_key ?: 'xxx'];
$apiParamsPaged = ['key' => Auth::user()->api_key ?: 'xxx', 'per_page' => 10];
?>
<h3>Documentation</h3>

@if (!Auth::user()->api_key)
	<div class="alert alert-warning">
		Consider generating an API key before reading the documentation - you'll get working example links that you can test
		out right in your browser.
	</div>
@endif

<h4 class="mt45">General - Requests</h4>

<p>
	All requests to the API have to include at least one parameter - your API key as the <b>key</b> parameter.
	Depending on the endpoint, additional parameters may be required or optionally available. See below for details about
	endpoints and their parameters.
</p>

<p>There are two types of requests:</p>

<ul>
	<li><b>GET</b> - for retrieving data</li>
	<li><b>POST</b> - for changing data</li>
</ul>

<p>
	For each endpoint we specify whether it's a GET or POST request. You must use the specified HTTP method, otherwise
	your requests will fail.
</p>

<h4 class="mt45">General - Responses</h4>

<p>
	All responses will be in JSON format unless a given endpoint allows specifying a parameter that changes the output format.
	If the response is empty or not in the expected format then it indicates a connection issue or a serious problem with
	the API - normally you'll get a JSON response even for internal server errors.
</p>

<p>
	The JSON response will look something like this:
</p>

<pre>
{
	"status": "success" <span class="text-success">- Either "success" or "error"</span>
	"current_page": 1,  <span class="text-success">- For paginated results, current page number (pages start at 1)</span>
	"total_pages": 4,   <span class="text-success">- For paginated results, total number of pages</span>
	"total_results": 40,<span class="text-success">- For paginated results, total number of results</span>
	"per_page": 10,     <span class="text-success">- For paginated results, number of results per page</span>

	<span class="text-success">For error responses data will be an error string.</span>
	<span class="text-success">For success responses it will be an array or object, depending on what's returned.</span>
	"data": [
		{
			"name": "Sony Xperia Z",
			"status": "In Stock"
		},
		...
	]
}
</pre>

<h4 class="mt45">Endpoints</h4>

<h5 class="mt45">
	<span class="bg-primary p5">GET</span> Stock <i class="fa fa-arrow-right"></i> View
</h5>

<p>{!! link_to_route('api.stock', null, $apiParamsPaged, ['target' => '_blank']) !!}</p>
<p class="small">Counterpart of {!! link_to_route('stock', 'this page', [], ['target' => '_blank']) !!}</p>

<p>Parameters:</p>

<ul>
	<li><b>key (required)</b></li>
	<li>
		<b>output</b> (optional) - you can set it to "csv" to get the results as a CSV file. Please note that CSV results
		can't be paginated - if you specify this option, you'll get full stock in one response. You should check if the
		content you retrieve consists of one row with "empty" in its only column - that's what we'll return if there's no
		results for the given query (returning an empty string would be confusing).
	</li>
	<li><b>term</b> (optional) - search string</li>
	<li><b>grade</b> (optional) - one of: "{{ implode('", "', Stock::getAvailableGrades()) }}"</li>
	<li><b>condition</b> (optional) - one of: "{{ implode('", "', Stock::getAvailableConditions()) }}"</li>
	@if (count(Auth::user()->allowed_statuses_viewing) > 1)
		<li><b>status</b> (optional) - one of: "{{ implode('", "', Auth::user()->allowed_statuses_viewing) }}"</li>
	@endif
	<li><b>network</b> (optional) - one of: "{{ implode('", "', Stock::getAvailableNetworks()) }}"</li>
	<li><b>page</b> - number of page of results to retrieve</li>
	<li><b>per_page</b> - how many results per page should be returned (max 1000)</li>
</ul>

<h5 class="mt45">
	<span class="bg-primary p5">POST</span> Unlocks <i class="fa fa-arrow-right"></i> Order
</h5>

<p>{{ route('api.unlocks.own-stock.new-order-save') }}</p>
<p class="small">Counterpart of {!! link_to_route('unlocks.own-stock.new-order-save', 'this page', [], ['target' => '_blank']) !!}</p>

<p>Parameters:</p>

<ul>
	<li><b>key (required)</b></li>
	<li><b>imeis_list (required)</b> - one or more IMEI numbers, separated by new lines, spaces or commas</li>
	<li><b>network (required)</b> - one of "{{ implode('", "', Pricing::getAvailableNetworks()) }}"</li>
	<li><b>models (required)</b> - one of "{{ implode('", "', Pricing::getAvailableModels()) }}"</li>
</ul>

<p>Response:</p>

<pre>
{
	"status": "success",
	"data": { "id": 85 } <span class="text-success">- Id of the created order.</span>
}
</pre>

<h5 class="mt45">
	<span class="bg-primary p5">GET</span> Unlocks <i class="fa fa-arrow-right"></i> Status
</h5>

<p>{{ route('api.unlocks') }}</p>

<p>Parameters:</p>

<ul>
	<li><b>key (required)</b></li>
	<li><b>imeis_list (required)</b> - one or more IMEI numbers, separated by new lines, spaces or commas</li>
</ul>

<p>Response:</p>

<pre>
{
	"status": "success",
	"data": {
		"123456789012345": {
			<span class="text-success">If the unlock is in our database, its status will be in this field:</span>
			"status": "New",
			<span class="text-success">Additional status description will be in this field:</span>
			"message": "We already have your unlock processing and we will email you shortly."
		},
		"234567890123456": {
			<span class="text-success">If we didn't find the unlock in our database, the status field will be set to false:</span>
			"status": false,
			<span class="text-success">Additional error information will be provided in the message field:</span>
			"message": "No unlock found."
		}
	}
}
</pre>

<h5 class="mt45">
	<span class="bg-primary p5">GET</span> Orders <i class="fa fa-arrow-right"></i> List
</h5>

<p>{!! link_to_route('api.sales', null, $apiParamsPaged, ['target' => '_blank']) !!}</p>
<p class="small">Counterpart of {!! link_to_route('sales', 'this page', [], ['target' => '_blank']) !!}</p>

<p>Parameters:</p>

<ul>
	<li><b>key (required)</b></li>
	<li><b>status</b> (optional) - one of "{{ implode('", "', array_keys(Invoice::getAvailableStatusesWithKeys())) }}"</li>
	<li><b>imei</b> (optional) - IMEI of one of the sold items</li>
	<li><b>page</b> - number of page of results to retrieve</li>
	<li><b>per_page</b> - how many results per page should be returned (max 1000)</li>
</ul>

<p>Response:</p>

<pre>
{
	"status":"success",
	"current_page": 1,
	"total_pages": 1,
	"total_results": 5,
	"per_page": 10,
	"data": [
		{
			"id": 788,
			"created_at": "2017-08-11 13:55",
			"item_count": 1,
			"amount": 17.8,
			"amount_formatted": "£17.80",
			"invoice_status": "open"
		},
		<span class="text-success">...</span>
	]
}
</pre>

<a id="api-docs-orders-order-details"></a>
<h5 class="mt45">
	<span class="bg-primary p5">GET</span> Orders <i class="fa fa-arrow-right"></i> Order Details
</h5>

<p>{{ route('api.sales', $apiParams + ['id' => 1]) }}</p>

<p>Parameters:</p>

<ul>
	<li><b>key (required)</b></li>
	<li><b>id (required)</b> - id of the sale</li>
</ul>

<p>Response:</p>

<pre>
{
	"status": "success",
	"data": {
		"id":788,
		"invoice_status": "open",
		"created_at": "2017-08-11 13:55",
		"item_count": 1,
		"amount": 17.8,
		"amount_formatted": "£17.80",
		"tracking_number": "",
		"items": [
			{
				<span class="text-success">Stock item details...</span>
			},
			{
				<span class="text-success">Stock item details...</span>
			},
			<span class="text-success">...</span>
		]
	}
}
</pre>

<h5 class="mt45">
	<span class="bg-primary p5">POST</span> Orders <i class="fa fa-arrow-right"></i> Create
</h5>

<p>{{ route('api.sales.save') }}</p>

<p>Parameters:</p>

<ul>
	<li><b>key (required)</b></li>
	<li>
		<p><b>items (required)</b> - stock items you want to order. This is best explained through an example:</p>
		<pre>
Array
(
	[key] => ***
	[items] => Array <span class="text-success">- You put the order items in an array</span>
		(
			[6573] => Array <span class="text-success">- The array is indexed by ids of the items you want to order</span>
				(
					[price] => 15 <span class="text-success">- Each array element contains the price of the item.</span>
				)	              <span class="text-success">  It's used to ensure the prices haven't changed since you</span>
					              <span class="text-success">  retrieved information about the stock items.</span>
			[6574] => Array
				(
					[price] => 25
				)
				<span class="text-success">...</span>
		)
)
		</pre>
		<p>Here's the same data as a query string:</p>
		<div class="well">items[6573][price]=15&items[6574][price]=25</div>
	</li>
</ul>

<p>Response:</p>

<p>
	Response is the same as for <a href="#api-docs-orders-order-details">order details</a> as we return the details for
	the newly created order.
</p>

<h5 class="mt45">
	<span class="bg-primary p5">GET</span> My Account <i class="fa fa-arrow-right"></i> Get Balance
</h5>

<p>{!! link_to_route('api.account.get-balance', null, $apiParams, ['target' => '_blank']) !!}</p>

<p>Parameters</p>

<ul>
	<li><b>key (required)</b></li>
</ul>

<p>Response:</p>

<pre>
{
	"status": "success",
	"data": { "balance": "£11.76" }
}
</pre>
