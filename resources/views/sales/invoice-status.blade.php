<?php
use App\Models\Invoice;
?>

@if (Auth::user()->type !== 'user' && in_array($sale->invoice_status, [Invoice::STATUS_OPEN]))
	<div class="dropdown ib">
		<button class="btn btn-default btn-xs dropdown-toggle" type="button" data-toggle="dropdown">
			<span class="caret"></span>
		</button>
		<div class="dropdown-menu">
			<li>
				<a href="javascript:" class="change-status" data-status="{{ Invoice::STATUS_PAID_ON_INVOICE }}">
					Mark as <em>Paid on Invoice</em>
				</a>
				<a href="javascript:" class="change-status" data-status="{{ Invoice::STATUS_PAID }}">
					Mark as <em>Paid</em>
				</a>
			</li>
			@if(!$sale->picked)
				<li>
					<a href="javascript:" class="change-status" data-status="picked">
						Mark as <em>Picked</em>
					</a>
				</li>
			@elseif($sale->picked)
				<li>
					<a href="javascript:" class="change-status" data-status="unpicked">
						Mark as <em>Unpicked</em>
					</a>
				</li>
			@endif
		</div>
	</div>
@endif

@if (Auth::user()->type !== 'user' && in_array($sale->invoice_status, [Invoice::STATUS_PAID, Invoice::STATUS_READY_FOR_DISPATCH]))
	<div class="dropdown ib">
		<button class="btn btn-default btn-xs dropdown-toggle" type="button" data-toggle="dropdown">
			<span class="caret"></span>
		</button>
		<div class="dropdown-menu">
			<li>
				<a href="javascript:" class="change-status" data-status="{{ Invoice::STATUS_READY_FOR_DISPATCH }}">
					Mark as <em>Ready for dispatch</em>
				</a>
			</li>
			<li>
				<a href="javascript:" class="change-status" data-status="{{ Invoice::STATUS_DISPATCHED }}">
					Mark as <em>Dispatched</em>
				</a>
			</li>
			@if(!$sale->picked)
				<li>
					<a href="javascript:" class="change-status" data-status="picked">
						Mark as <em>Picked</em>
					</a>
				</li>
			@elseif($sale->picked)
				<li>
					<a href="javascript:" class="change-status" data-status="unpicked">
						Mark as <em>Unpicked</em>
					</a>
				</li>
			@endif
		</div>
	</div>
@endif

<div>{{ ucfirst($sale->invoice_status_alt) }}</div>
@if(Auth::user()->type === 'admin' && $sale->picked)
	<span class="text-warning">Picked</span>
@endif
