<style>
	@page {
		margin:0;
	}
	body {
	}
	div {
		padding:2mm;
		font-size: 10px;
		font-family: Calibri, sans-serif;
	}
</style>
<div>

	<p style="text-align: center">
		<b>Recomm</b><br/>
		<b>[TODO]</b><br/>
		<b>[TODO]</b><br/>
		<b>[TODO]</b><br/>
		<b>[TODO]</b><br/>
		<b>[TODO]</b><br/>
		<b>[TODO]</b>
	</p>
	<p style="text-align: center">
		<b>VAT:</b> GB123482531
	</p>

	<p style="text-align: center">
		<b>W:</b> www.recomm.co.uk
	</p>

	<p style="text-align: center">
		<b>T:</b> 01494 303600
	</p>

	<p style="text-align: center">
		<b>Date:</b> {{ $item->sale->created_at->format("d/m/Y H:i") }}
	</p>

	<p style="text-align: center">
		<b>Sale ID:</b> {{ $item->sale->id }}
	</p>

	<p style="text-align: center">
		<b>SALES RECEIPT</b>
	</p>

	<p>
		<b>Item(s):</b><br/>

		{{ $item->long_name }} @if( $item->imei ) - IMEI: {{ $item->imei }} @endif
		<br/>
		{{ $item->sale_price_formatted }}

	</p>

	<p>
		<b>Total: {{ $item->sale_price_formatted }}</b>
	</p>

	<p>
		<b>Amount Paid: {{ $item->sale_price_formatted }}</b>
	</p>

	<p>
		Please keep this receipt for your records. This receipt acts as your warranty:
	</p>

	<p>
		All phones & tablets – 24 months
	</p>

	<p>
		Repairs – 12 month on parts only
	</p>

	<p>
		Accessories: 12 months
	</p>

	<p style="text-align: center;">
		Thank you for shopping with us!
	</p>

	<p style="text-align: center; font-size: 8px;">
		Terms and Conditions apply, please visit www.recomm.co.uk/terms for full T&C's.
	</p>

	<p style="text-align: center; font-size: 8px;"><b>No refunds are offered on this purchase, exchange only.</b></p>
</div>