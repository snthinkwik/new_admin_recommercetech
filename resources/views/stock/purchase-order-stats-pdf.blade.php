<!doctype html>
<html>
<head>
	<title>Purchase Order Stats</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<link rel="stylesheet" href="{{public_path("css/bootstrap-pdf.min.css")}}" media="all">
	<style>
		thead:before, thead:after { display: none; }
		tbody:before, tbody:after { display: none; }
	</style>
</head>
<body>
<div class="container">
	<h3>Purchase Order Stats - {{ $stats->purchase_order_number }}</h3>
	<table class="table table-bordered">
		<tr class="success"><th>No. Devices</th><td>{{ $stats->total }}</td></tr>
		<tr class="info"><th>No. Items Sold</th><td>{{ $stats->items_sold }}</td></tr>
		<tr class="info"><th>No. Items To Sell</th><td>{{ $stats->items_to_sell }}</td></tr>
		<tr class="info"><th>No. Items Returned</th><td>{{ $stats->total > 0 ? number_format($stats->items_returned/$stats->total*100, 2) : '0.00%'}}% ({{ $stats->items_returned }}/{{ $stats->total }})</td></tr>
		<tr class="info"><th>Devices Tested</th><td>{{ $stats->tested }} out of {{ $stats->total_tested }}</td></tr>

		<tr class="success"><th>% fully working - no touch id</th><td>{{ number_format($stats->fully_working_no_touch_id / $stats->total * 100) }}%</td></tr>
		<tr class="success"><th>% fully working</th><td>{{ number_format($stats->fully_working / $stats->total * 100) }}%</td></tr>
		<tr class="success"><th>% minor fault</th><td>{{ number_format($stats->minor_fault / $stats->total * 100) }}%</td></tr>
		<tr class="success"><th>% major fault</th><td>{{ number_format($stats->major_fault / $stats->total * 100) }}%</td></tr>
		<tr class="success"><th>% no signs of life</th><td>{{ number_format($stats->broken / $stats->total * 100) }}%</td></tr>

		<tr class="info"><th>% grade A</th><td>{{ number_format($stats->condition_a / $stats->total * 100) }}%</td></tr>
		<tr class="info"><th>% grade B</th><td>{{ number_format($stats->condition_b / $stats->total * 100) }}%</td></tr>
		<tr class="info"><th>% grade C</th><td>{{ number_format($stats->condition_c / $stats->total * 100) }}%</td></tr>

		<tr class="success"><th>% unlocked</th><td>{{ number_format($stats->networks->unlocked / $stats->total * 100) }}%</td></tr>
		<tr class="success"><th>% unknown</th><td>{{ number_format($stats->networks->unknown / $stats->total * 100) }}%</td></tr>
		<tr class="success"><th>% Vodafone</th><td>{{ number_format($stats->networks->vodafone / $stats->total * 100) }}%</td></tr>
		<tr class="success"><th>% EE</th><td>{{ number_format($stats->networks->ee / $stats->total * 100) }}%</td></tr>
		<tr class="success"><th>% Three</th><td>{{ number_format($stats->networks->three / $stats->total * 100) }}%</td></tr>
		<tr class="success"><th>% O2</th><td>{{ number_format($stats->networks->o2 / $stats->total * 100) }}%</td></tr>
		<tr class="success"><th>% other</th><td>{{ number_format($stats->networks->other / $stats->total * 100) }}%</td></tr>

		<tr class="info"><th>Total Purchase Price</th><td>£{{ number_format($stats->purchase_price) }}</td></tr>
		<tr class="info"><th>Total Sales Price</th><td>£{{ number_format($stats->sales_price) }}</td></tr>
		<tr class="info"><th>Gross Profit</th><td>£{{ number_format($stats->gross_profit) }}</td></tr>
		<tr class="info"><th>Profit Ratio</th><td>{{ number_format($stats->profit_ratio*100, 2) }}%</td></tr>
	</table>
</div>
</body>
</html>