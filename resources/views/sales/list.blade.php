@if (!count($sales))
	<div class="alert alert-warning">No sales.</div>
@else
	<div class="table small stock table-h-sticky">
		<table id="sales" class="table" style="font-size: 12px">
			<thead>
			<tr>
				<th>Recomm Order Id</th>
				@if (Auth::user()->type !== 'user')
					<th>Customer</th>
					<th>Buyers Ref</th>
					<th>Ship to</th>
				@endif
				<th>Date</th>
				<th>Item count</th>
				<th>Items</th>
				<th>Sale VAT Type</th>
				<th>Sale Total incCarriage</th>
				<th>Sale Total ex Vat incCarriage</th>
				@if(Auth::user()->type !== 'user')
					<th>Total Purchase Cost</th>
					<th>Profit &pound;</th>
				@endif

				<th>Profit%</th>
				<th>Marg VAT</th>
				<th>True Profit</th>
				<th>Platform Name</th>
				<th>True Profit %</th>
				<th>Status</th>
				<th>Delivery Notes</th>
				<th>Invoice</th>
				<th>Seller Fees + Accessories Cost</th>
				<th>Est Shipping Cost</th>
				<th>Est Net Profit</th>
				<th>Est Net Profit (Non P/S) £</th>
{{--				<th>Est Net Profit%</th>--}}
				<th>Recomm P/S £</th>
				<th align="center">Items Sold Non P/S</th>
				<th>Items Sold P/S</th>
				<th >Total Est  Net Profit £</th>
				<th>Net Profit %</th>
				<th></th>
			</tr>
			</thead>
			<tbody>
			@foreach ($sales as $sale)
				@include('sales.item')
			@endforeach
			</tbody>
		</table>
	</div>
@endif