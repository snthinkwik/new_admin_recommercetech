<?php
use App\Invoice;
use Illuminate\Support\Facades\Request;

$invoicing = app('App\Contracts\Invoicing');
?>

@extends('app')
@section('title', Auth::user() ? Auth::user()->texts['sales']['title'] : 'Sales')

@section('content')

	<div class="container-fluid">
		@include('messages')

		@if (Auth::user() && Auth::user()->type !== 'user')
			<div class="mb15">
				<a href="{{ route('sales.modify') }}">Modify order</a>
			</div>
		@endif

		@if (!empty($saleJustCreated))
			<div id="sale-created" class="alert alert-success" role="alert">
				<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>
				<p>Sale created.</p>
				@if($saleJustCreated->other_recycler)
					<p><b>Recycler: {{ $saleJustCreated->other_recycler }}</b></p>
				@else
					<p>
						Please now pay
						<span class="amount">
						@if ($saleJustCreated->invoice_creation_status === 'success')
								{{ $saleJustCreated->amount_formatted }}
							@else
                                (<img class="invoice-in-progress" src="{{ asset('/img/ajax-loader-success.gif') }}" aria-hidden="true">
                                <em>amount loading</em>)
							@endif
					</span>
						by Bank Transfer to:
					</p>

					<p>
						Account Name: Recommerce Ltd<br/>
						Account no: 49869160<br/>
						Sort Code: 30-98-97<br/>
						Bank: Lloyds
					</p>
				@endif
			</div>
		@endif

		@include('sales.search-form')
		<div id="universal-table-wrapper">
			@include('sales.list')
		</div>

		<div id="stock-pagination-wrapper">
			{!! $sales->render() !!}
		</div>
	</div>

	@include('sales.tracking-number-modal')

@endsection

@section('nav-right')
	@if (Auth::user()->type !== 'user' && in_array(Auth::user()->admin_type, ['admin', 'manager']))
		<div class="navbar-form navbar-right pr0">
			<div class="btn-group">
				<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
					Export CSV <span class="caret"></span>
				</button>
				<ul class="dropdown-menu">
					<li><a href="" data-toggle="modal" data-target="#modalLong" id="date">Export Item By Date</a></li>
					<li><a href="" data-toggle="modal" data-target="#modalLong" id="customer_name">Export Item By Customer Name</a></li>
					<li><a href="" data-toggle="modal" data-target="#modalLong" id="status_list">Export Item By Status</a></li>



				</ul>
			</div>
		</div>
	@endif

	<div id="basket-wrapper" class="navbar-right pr0">
		@include('basket.navbar')
	</div>


@endsection

@section('pre-scripts')
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>

	<script>
	$("#date").on('click',function () {
	$("#sale_date").show();
	$("#exampleModalLongTitleSales").html("Date")
		$("#customer").hide();
		$("#status").hide();
		$("#input_customer_name").val("");
		$("#input_status").val("");

	})
	$("#customer_name").on('click',function () {
		$("#customer").show();
		$("#exampleModalLongTitleSales").html("Customer Name")
		$("#sale_date").hide();
		$("#status").hide();
		$("#input_start_date").val("");
		$("#input_last_date").val("");
		$("#input_status").val("");

	})
	$("#status_list").on('click',function () {
		$("#status").show();
		$("#exampleModalLongTitleSales").html("Status")
		$("#sale_date").hide();
		$("#customer").hide();
		$("#input_start_date").val("");
		$("#input_last_date").val("");
		$("#input_customer_name").val("");

	})

</script>
@endsection

