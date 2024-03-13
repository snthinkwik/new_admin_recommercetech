@extends('app')

@section('title', 'Pay for Order - Select Payment Method')

@section('content')

	<div class="container">
		<div class="row">
			<div class="col-md-12">
				<ul class="nav nav-tabs nav-justified">
					<li class="active"><a data-toggle="tab" href="#bank-transfer">Bank Transfer</a></li>
					<li><a data-toggle="tab" href="#sage-pay">Sage Pay</a>
					</li>
				</ul>
				<div class="tab-content mt50">
					<div class="row tab-pane fade in active">
						<div class="col-md-6">
							<div id="bank-transfer" class="tab-pane fade in active">
								<div>
									<p><b>Thank you for your order, it's now time to pay.</b></p>
									<p><b>Our preferred payment method is by Bank Transfer to the account below:</b></p>
									<p>
										Account Name: Recommerce Ltd<br/>
										Account no: [TO DO]<br/>
										Sort Code: [TO DO]<br/>
										IBAN: [TO DO]<br/>
										BIC: [TO DO]<br/>
										Bank: [TO DO]
									</p>
									<h5><b>Payment Amount: {{ $sale->amount_formatted }}</b></h5>
								</div>
							</div>
						</div>
						<div class="col-md-6">
							<div id="sage-pay2" class="tab-pane fade in">
								<div class="mh20">
									<h4 class="ml35"><b>Pay by Card Here <i class="fa fa-arrow-up"></i></b></h4>

									@if($sale->invoice_details)
										<p><b>*Please note that there is a 1.6% surcharge for paying by card</b></p>
									@endif
								</div>
							</div>
							<div id="sage-pay" class="tab-pane fade">
								<div class="mh20">
									<div class="alert alert-info">
										Payment Amount including Card Processing Fee: {{ money_format($sale->invoice_total_amount + $sale->invoice_total_amount*0.016+0.12) }}
										{!! BsForm::open(['route' => 'sales.pay']) !!}
										{!! Form::hidden('id', $sale->id) !!}
										{!! BsForm::groupSubmit('Pay', ['class' => 'confirmed btn-block', 'data-confirm' => 'Proceed with Sage Pay?']) !!}
										{!! BsForm::close() !!}
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

@endsection
