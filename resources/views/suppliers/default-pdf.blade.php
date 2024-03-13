<!doctype html>
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>Recomm Return RMA</title>
	<link rel="stylesheet" href="{{public_path("css/bootstrap-pdf.min.css")}}" media="all">
	<style>
		thead:before, thead:after { display: none; }
		tbody:before, tbody:after { display: none; }
		* { font-family: DejaVu Sans; }
		table {
			font-size: smaller;
		}
	</style>
</head>
<body>

<table style="margin-bottom: 50px; width: 100%;" >
	<tr>
		<td style="width:30%"><img src="{{ public_path("img/logo.png") }}"></td>
		<td style="width:70%; padding-left:5em;"><h1>Supplier Returns</h1></td>
	</tr>
</table>

<p><b>Supplier:</b> {{ $supplierReturn->supplier->name }}</p>
<p><b>Return Address:</b> {{ $supplierReturn->supplier->address_long }}</p>

<table class="table table-bordered" style="margin-top:10px;">
	<tr>
		<th>Date</th>
		<th>Supplier Ref</th>
		<th>IMEI</th>
		<th>Model</th>
		<th>Fault</th>
		<th>Purchase Price</th>
	</tr>
	@foreach($supplierReturn->items as $item)
		<tr>
			<td>{{ $item->stock->purchase_date ? $item->stock->purchase_date->format('d.m.Y') : '' }}</td>
			<td>{{ $item->stock->third_party_ref }}</td>
			<td>{{ $item->stock->imei ? : $item->stock->serial }}</td>
			<td>{{ $item->stock->name }}</td>
			<td>{{ $item->reason }}</td>
			<td>&pound;{{ number_format($item->stock->purchase_price, 2) }}</td>
		</tr>
	@endforeach
</table>

</body>