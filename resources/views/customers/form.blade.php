{!! Form::model($customer, ['route' => 'customers.save']) !!}
	@if (isset($showWarning) && $showWarning)
		<div class="alert alert-warning small">
			Changing the customer data here will update it in {{ $invoicing->getSystemName() }} globally for this customer,
			not just for this sale.
		</div>
	@endif

	<div class="row">
		<div class="col-md-6">
			<div class="form-group">
				<label for="customer-first-name">First name</label>
				{!! Form::text('first_name', null, ['class' => 'form-control input-sm', 'id' => 'customer-first-name']) !!}
			</div>
			
			<div class="form-group">
				<label for="customer-last-name">Last name</label>
				{!! Form::text('last_name', null, ['class' => 'form-control input-sm', 'id' => 'customer-last-name']) !!}
			</div>
			
			<div class="form-group">
				<label for="customer-company-name">Company name</label>
				{!! Form::text('company_name', null, ['class' => 'form-control input-sm', 'id' => 'customer-company-name']) !!}
			</div>
			
			<div class="form-group">
				<label for="customer-email">Email</label>
				{!! Form::text('email', null, ['class' => 'form-control input-sm', 'id' => 'customer-email']) !!}
			</div>
		</div>
	</div>

	<hr>

	<div class="row">
		<div class="col-md-6">
			<div class="form-group">
				<label for="customer-billing-address-line1">Billing address: Line 1</label>
				{!! Form::text('billing_address[line1]', null, ['class' => 'form-control input-sm', 'id' => 'customer-billing-address-line1']) !!}
			</div>
			
			<div class="form-group">
				<label for="customer-billing-address-line2">Billing address: Line 2</label>
				{!! Form::text('billing_address[line2]', null, ['class' => 'form-control input-sm', 'id' => 'customer-billing-address-line2']) !!}
			</div>
			
			<div class="form-group">
				<label for="customer-billing-address-city">Billing address: City</label>
				{!! Form::text('billing_address[city]', null, ['class' => 'form-control input-sm', 'id' => 'customer-billing-address-city']) !!}
			</div>

			<div class="form-group">
				<label for="customer-billing-address-county">Billing address: County</label>
				{!! Form::text('billing_address[county]', null, ['class' => 'form-control input-sm', 'id' => 'customer-billing-address-county']) !!}
			</div>

			<div class="form-group">
				<label for="customer-billing-address-postcode">Billing address: postcode</label>
				{!! Form::text('billing_address[postcode]', null, ['class' => 'form-control input-sm', 'id' => 'customer-billing-address-postcode']) !!}
			</div>
			<div class="form-group">
				<label for="customer-billing-address-country">Billing address: Country</label>
				{!! Form::text('billing_address[country]', null, ['class' => 'form-control input-sm', 'id' => 'customer-billing-address-country']) !!}
			</div>
		</div>
		<div class="col-md-6">
			<div class="form-group">
				<label for="customer-shipping-address-line1">Shipping address: Line 1</label>
				{!! Form::text('shipping_address[line1]', null, ['class' => 'form-control input-sm', 'id' => 'customer-shipping-address-line1']) !!}
			</div>
			
			<div class="form-group">
				<label for="customer-shipping-address-line2">Shipping address: Line 2</label>
				{!! Form::text('shipping_address[line2]', null, ['class' => 'form-control input-sm', 'id' => 'customer-shipping-address-line2']) !!}
			</div>
			
			<div class="form-group">
				<label for="customer-shipping-address-city">Shipping address: City</label>
				{!! Form::text('shipping_address[city]', null, ['class' => 'form-control input-sm', 'id' => 'customer-shipping-address-city']) !!}
			</div>

			<div class="form-group">
				<label for="customer-shipping-address-county">Shipping address: County</label>
				{!! Form::text('shipping_address[county]', null, ['class' => 'form-control input-sm', 'id' => 'customer-shipping-address-county']) !!}
			</div>
			

			
			<div class="form-group">
				<label for="customer-shipping-address-postcode">Shipping address: postcode</label>
				{!! Form::text('shipping_address[postcode]', null, ['class' => 'form-control input-sm', 'id' => 'customer-shipping-address-postcode']) !!}
			</div>
			<div class="form-group">
				<label for="customer-shipping-address-country">Shipping address: Country</label>
				{!! Form::text('shipping_address[country]', null, ['class' => 'form-control input-sm', 'id' => 'customer-shipping-address-country']) !!}
			</div>
		</div>
	</div>
{!! Form::close() !!}