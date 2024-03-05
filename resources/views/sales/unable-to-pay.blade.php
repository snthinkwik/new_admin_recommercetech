@extends('app')

@section('title', "Pay for invoice")

@section('content')
	<div class="container">
		<h1>Order payment</h1>
		<div class="row">
			<div class="col-md-6">
				<div class="well">
					<p>Payment method is preferred by Bank Transfer:</p>
					<p>
						Account Name: Recommerce Ltd<br/>
						Account no: [TO DO]<br/>
						Sort Code: [TO DO]<br/>
						IBAN: [TO DO]<br/>
						BIC: [TO DO]<br/>
						Bank: [TO DO]
					</p>

					<p>
						Please note that paying by card is not possible as your order total is over £1,000.<br/>
						If you would still like to pay by Card we recommend <a href="https://transferwise.com/" target="_blank">https://transferwise.com/</a>
					</p>
				</div>
			</div>
		</div>
	</div>
@endsection