<?php
use App\Invoice;
?>
<div class="col-md-6">
	<div class="panel panel-default">
		<div class="panel-heading text-center">
			<h4>New Orders</h4>
		</div>
		<div class="panel-body">
			@if(count($newOrders))
				<table class="table table-bordered table-hover">
					<thead>
					<tr>
						<th>Customer Name</th>
						<th>No. of Devices</th>
						<th>Order Value</th>
					</tr>
					</thead>
					<tbody>
					@foreach($newOrders as $sale)
						<tr @if($sale->invoice_status == Invoice::STATUS_READY_FOR_DISPATCH) class="success" @endif>
							<td>
								@if($sale->customer_api_id)
									@if(isset($customers[$sale->customer_api_id]))
										{{ $customers[$sale->customer_api_id]->full_name }}
									@else
										-
									@endif
								@else
									<b class="text-danger">Error - No Customer</b>
								@endif
							</td>
							<td>{{ $sale->stock->count() }}</td>
							<td>{{ $sale->amount_formatted }}</td>
						</tr>
					@endforeach
					</tbody>
				</table>
			@endif
		</div>
	</div>
</div>

<div class="col-md-6">
	<div class="panel panel-default">
		<div class="panel-heading text-center">
			<h4>Orders Awaiting Dispatch</h4>
		</div>
		<div class="panel-body">
			@if(count($sales))
				<table class="table table-bordered table-hover">
					<thead>
						<tr>
							<th>Customer Name</th>
							<th>No. of Devices</th>
							<th>Order Value</th>
						</tr>
					</thead>
					<tbody>
						@foreach($sales as $sale)
							<tr @if($sale->invoice_status == Invoice::STATUS_READY_FOR_DISPATCH) class="success" @endif>
								<td>
									@if($sale->customer_api_id)
										@if(isset($customers[$sale->customer_api_id]))
											{{ $customers[$sale->customer_api_id]->full_name }}
										@else
											-
										@endif
									@else
										<b class="text-danger">Error - No Customer</b>
									@endif
								</td>
								<td>{{ $sale->stock->count() }}</td>
								<td>{{ $sale->amount_formatted }}</td>
							</tr>
						@endforeach
					</tbody>
				</table>
			@endif
		</div>
	</div>
</div>