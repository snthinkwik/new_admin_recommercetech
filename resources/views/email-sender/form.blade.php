<?php
use App\Batch;
use App\Email;
use App\Country;
$batchIds = Batch::has('stock')->orderBy('id')->lists('id');
$countries = Country::orderBy('name')->lists('name');
$countries = array_combine($countries, $countries);
$brands = ['' => 'Recomm'] + Email::getAvailableBrandsWithKeys();
?>
@include('scripts', ['required' => 'ckeditor'])

{!! BsForm::model($email, ['route' => 'emails.save', 'files' => true], ['id-and-prefix' => 'email-create-form']) !!}
	<fieldset {!! isset($edit) && !$edit ? 'disabled' : '' !!}>
		@if ($email->exists)
			{!! BsForm::hidden('id') !!}
		@endif
		<div class="form-group">
			<label>Recipients</label>
			{!! BsForm::radio('to', Email::TO_REGISTERED, old() ? null : true, null, "Registered Users only") !!}
			{!! BsForm::radio('to', Email::TO_UNREGISTERED, null, null, "Unregistered Users only") !!}
			{!! BsForm::radio('to', Email::TO_EVERYONE, null, null, "Everyone") !!}
			<label>Options <small>(If Selected, Will be sent to registered, even if recipients above will be different)</small></label>
			{!! BsForm::radio('option', Email::OPTION_NONE, old() ? null : true, null, "None") !!}
			{!! BsForm::radio('option', Email::OPTION_BOUGHT_NOT_LAST_45_DAYS, null, null, "People who have bought from us but not in the last 45 days") !!}
			{!! BsForm::radio('option', Email::OPTION_NEVER_BOUGHT, null, null, "People who have never bought from us") !!}
			{!! BsForm::radio('option', Email::OPTION_PAID_NOT_DISPATCHED, null, null, "Paid but not dispatched orders") !!}
			{!! BsForm::radio('option', Email::OPTION_COUNTRY, null, null, "Select Country") !!}
			{!! Form::label('Country (Option Country must be selected)') !!}
			{!! BsForm::select('option_details', $countries, isset($email->option_details) ? $email->option_details : "United Kingdom") !!}
			{!! BsForm::groupSelect('brand', $brands, null, ['data-field' => '%%BRAND%%']) !!}
		</div>
		<label for="email-create-form-subject">Subject</label>
		<div class="form-group">
			<a class="btn btn-default btn-xs subject-field" href="javascript:" data-field="%%FIRST_NAME%%">First name</a>
			<a class="btn btn-default btn-xs subject-field" href="javascript:" data-field="%%LAST_NAME%%">Last name</a>
			<a class="btn btn-default btn-xs subject-field" href="javascript:" data-field="%%FULL_NAME%%">Full name</a>
			<a class="btn btn-default btn-xs subject-field" href="javascript:" data-field="%%COMPANY_NAME%%">Company name</a>
			<a class="btn btn-default btn-xs subject-field" href="javascript:" data-field="%%ADDRESS%%">Address</a>
			<a class="btn btn-default btn-xs subject-field" href="javascript:" data-field="%%ADDRESS_LINE1%%">Address - line 1</a>
			<a class="btn btn-default btn-xs subject-field" href="javascript:" data-field="%%ADDRESS_LINE2%%">Address - line 2</a>
			<a class="btn btn-default btn-xs subject-field" href="javascript:" data-field="%%ADDRESS_CITY%%">Address - city</a>
			<a class="btn btn-default btn-xs subject-field" href="javascript:" data-field="%%ADDRESS_COUNTY%%">Address - county</a>
			<a class="btn btn-default btn-xs subject-field" href="javascript:" data-field="%%ADDRESS_POSTCODE%%">Address - postcode</a>
			<a class="btn btn-default btn-xs subject-field" href="javascript:" data-field="%%ADDRESS_COUNTRY%%">Address - country</a>
			<a class="btn btn-default btn-xs subject-field" href="javascript:" data-field="%%EMAIL%%">Email</a>
		</div>
		{!! BsForm::text('subject') !!}
		<label for="email-create-form-body">Body</label>
		<div class="form-group">
			<a class="btn btn-default btn-xs field" href="javascript:" data-field="%%FIRST_NAME%%">First name</a>
			<a class="btn btn-default btn-xs field" href="javascript:" data-field="%%LAST_NAME%%">Last name</a>
			<a class="btn btn-default btn-xs field" href="javascript:" data-field="%%FULL_NAME%%">Full name</a>
			<a class="btn btn-default btn-xs field" href="javascript:" data-field="%%COMPANY_NAME%%">Company name</a>
			<a class="btn btn-default btn-xs field" href="javascript:" data-field="%%ADDRESS%%">Address</a>
			<a class="btn btn-default btn-xs field" href="javascript:" data-field="%%ADDRESS_LINE1%%">Address - line 1</a>
			<a class="btn btn-default btn-xs field" href="javascript:" data-field="%%ADDRESS_LINE2%%">Address - line 2</a>
			<a class="btn btn-default btn-xs field" href="javascript:" data-field="%%ADDRESS_CITY%%">Address - city</a>
			<a class="btn btn-default btn-xs field" href="javascript:" data-field="%%ADDRESS_COUNTY%%">Address - county</a>
			<a class="btn btn-default btn-xs field" href="javascript:" data-field="%%ADDRESS_POSTCODE%%">Address - postcode</a>
			<a class="btn btn-default btn-xs field" href="javascript:" data-field="%%ADDRESS_COUNTRY%%">Address - country</a>
			<a class="btn btn-default btn-xs field" href="javascript:" data-field="%%EMAIL%%">Email</a>
		</div>
		{!! BsForm::groupTextArea('body', null, ['ROWS' => 15, 'cols' => 50], ['label' => false]) !!}
		{!! BsForm::groupText('from_email', 'sales@recomm.co.uk') !!}
		{!! BsForm::groupText('from_name') !!}
		<div class="form-group">
			<label>Attachment</label>
			<div class="form-inline">
				{!! BsForm::radio('attachment', Email::ATTACHMENT_NONE, old() ? null : true, null, "None") !!}
				{!! BsForm::radio('attachment', Email::ATTACHMENT_FILE, null, null, "File") !!}
				{!! BsForm::radio('attachment', Email::ATTACHMENT_BATCH, null, null, "Batch") !!}
			</div>
		</div>
		<div class="form-group hide @hasError('file')" data-attachment-type="{{ Email::ATTACHMENT_FILE }}">
			<label>Attachment file</label>
			{!! BsForm::file('file') !!}
			@error('file')
		</div>
		<div class="form-group hide" data-attachment-type="{{ Email::ATTACHMENT_BATCH }}">
			<label>Batch</label>
			{!! BsForm::select('batch_id', array_combine($batchIds, $batchIds)) !!}
		</div>
		<div class="form-group">
			{!! BsForm::submit('Send email', ['class' => 'confirmed', 'data-confirm' => "Are you sure you want to send this email?"]) !!}
			@if (!$email->exists)
				<div class="form-group pull-right">
				{!! BsForm::button('Save Draft', ['class' => 'btn btn-info save-draft']) !!}
				{!! BsForm::button('Test send', ['class' => 'btn btn-success test-send']) !!}
				</div>
			@endif
		</div>
	</fieldset>
	@if ($email->exists)
		<div class="form-group">
			{!! BsForm::button('Test send', ['class' => 'btn btn-success test-send']) !!}
		</div>
	@endif
{!! BsForm::close() !!}

@include('email-sender.test-send-modal')
@include('email-sender.save-draft-modal')