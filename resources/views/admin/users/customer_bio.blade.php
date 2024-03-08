<a class="btn btn-default btn-block" data-toggle="collapse" data-target="#customer_bio">Customer Bio</a>
<div class="panel panel-default collapse" id="customer_bio">
	<div class="panel-body">
		{!! Form::open() !!}
		{!! Form::hidden('user_id', $user->id) !!}
		{!! Form::label('vat_number', 'VAT Number') !!}
		{!! BsForm::text('vat_number', $user->vat_number) !!}
		{!! Form::label('customer_bio', 'Customer Bio') !!}
		{!! BsForm::textarea('customer_bio', $user->customer_bio) !!}
		{!! BsForm::submit('Save', ['class' => 'btn btn-primary btn-block']) !!}
		{!! Form::close() !!}
	</div>
</div>