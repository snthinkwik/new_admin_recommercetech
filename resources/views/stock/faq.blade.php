@extends('app')

@section('title', 'FAQ')

@section('content')

	<div class="container">
		<div class="row">
			<div class="col-md-10 col-md-offset-1">
				<div class="well well-faq">
					<h3>Contact Details</h3>
					<h5><b>Company Name: </b> Recommerce</h5>
					<h5>
						<b>Address:</b><br/>
						Suite A, 2nd Floor<br/>
						Apollo Centre<br/>
						Desborough Road<br/>
						High Wycombe<br/>
						Buckinghamshire<br/>
						HP11 2QW
					</h5>
					<h5><b>Contact Number:</b> 01494 303600</h5>
					<h5><b>Email Address:</b> <a href="mailto:support@recomm.co.uk">support@recomm.co.uk</a></h5>
				</div>

				<div class="well well-faq">
					<h3>How do batches work?</h3>
					<h5>The most prominent way Recomm sells stock is through the process of batches.
						There are two main types of batches – Fully Working and Minor Faults.
						The size of a batch can vary depending on what is in stock at the time.
						For example, a batch of Fully Working may have 10 devices or a batch of Minor Fault devices can have 30 or more.
						A take all price is allocated to the whole batch – individual devices cannot be removed.
						The batches are sold as locked network – you can view the networks of the devices through the stock system on your order.</h5>
				</div>

				<div class="well well-faq">
					<h3>Can I buy individual units?</h3>
					<h5>We no longer sell individual units – our selling method is through batches.</h5>
				</div>

				<div class="well well-faq">
					<h3>How can I pay?</h3>
					<h5>There are two methods in which you can make payment for your order:</h5>
					<h4><b>1 - Bank Transfer using the below details:</b></h4>
						<h5>Recommerce LTD<br/>
							Account number: 49869160<br/>
							Sort code: 30-98-97<br/>
							REF: Name/Invoice Number</h5>
					<h4><b>2 - Sage Pay</b></h4>
					<h5>This option is done online through the stock system.
						Once your order has been placed you can then click the option to pay through Sage Pay.
						It will then take you to a page where you will need to fill out all necessary details including
						registered card holder name and address etc. Sage Pay will then carry out further security
						checks and once all details are submitted and verified the payment will be processed.</h5>
				</div>

				<div class="well well-faq">
					<h3>How do you deliver?</h3>
					<h5>If you are located in the UK – UK mail is the courier we choose to use. Delivery is £6.50 + VAT and is guaranteed next business day delivery.</h5>
					<h5>If you are located outside the UK the international courier used is TNT. This is £29.00 + VAT. This is also next business day delivery.</h5>
				</div>

				<div class="well well-faq">
					<h3>Can I unlock my own stock?</h3>
					<h5>Yes you can! This can be done through the online stock system on your account.
						Under the Unlocks tab you can select ‘You Own Stock’, then select ‘new order’ and enter the IMEI’s and their networks which they are locked to.
						Once the order is placed you can then pay via Sage Pay which asks you to fill in a number of details regarding the card being used for payment.
						Once the payment is verified the unlock is sent off for processing. As soon as this is unlocked you will receive an email notification.</h5>
				</div>

				<div class="well well-faq">
					<h3>I have a problem with my order</h3>
					<h5>If you have any issues with your order please email support@recomm.co.uk or alternatively give us
						a call on 04194 303600 so you can be transferred to the right person depending on your query.</h5>
				</div>

				<div class="well well-faq">
					<h3>Track my order</h3>
					<h5>An email with your tracking number details would have been sent to the email address on the account.
						Alternatively, you can obtain your tracking number through your account on the stock system.
						Under the tab ‘My Orders’ select the details button on the order your looking to track.
						Your tracking number will then appear at the top. If you then use
						<a href="https://www.ukmail.com/manage-my-delivery/manage-my-delivery" target="_blank">Manage My Delivery</a>,
						enter the tracking number and you can then track your order.</h5>
				</div>

				<div class="well well-faq">
					<h3>Process of orders</h3>
					<h5>Once you have placed an order with us you will receive an email with the invoice attached.
						Payment is required immediately in order to hold the stock for you therefore may then also receive an auto email from our accounts department with a reminder if payment hasn’t been made in the allocated time period.
						Once payment has been made your order will be packed up for dispatch. Upon dispatch you will receive another email detailing the IMEI’s of the devices sent.
						The last email for you to receive is an email with the tracking number for the batch and a link to the UK Mail site to track this.</h5>
				</div>

				<div class="well well-faq">
					<h3>Can you reserve stock for me?</h3>
					<h5>Due to our larger customer base and the high level of supply and demand we do not reserve stock for any of our customers.</h5>
				</div>

				<div class="well well-faq">
					<h3>What is VAT Margin?</h3>
					<h5>As the devices sold are second hand goods they are on the VAT margin scheme.
						Please visit the <a href="https://www.gov.uk/vat-margin-schemes" target="_blank">Gov.UK</a> website for more information on this.</h5>
				</div>
			</div>
		</div>
	</div>


@endsection