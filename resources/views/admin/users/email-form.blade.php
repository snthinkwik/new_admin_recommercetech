<?php
use App\Models\Email;
use App\Models\Batch;
$batchIds = Batch::has('stock')->orderBy('id')->pluck('id')->toArray();

?>
@include('scripts', ['required' => 'ckeditor'])

{!! BsForm::model($email, ['route' => 'admin.users.emails.send', 'files' => true], ['id-and-prefix' => 'email-user-create-form']) !!}
<fieldset {!! isset($edit) && !$edit ? 'disabled' : '' !!}>
	{!! BsForm::hidden('user_id', $user->id) !!}
	<p><b>Email:</b> {{ $user->email }}</p>
	{!! BsForm::groupText('subject') !!}
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
	{!! BsForm::groupText('from_email', Auth::user()->email) !!}
	{!! BsForm::groupText('from_name', Auth::user()->full_name) !!}
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
		@error('file') @enderror
	</div>
	<div class="form-group hide" data-attachment-type="{{ Email::ATTACHMENT_BATCH }}">
		<label>Batch</label>
		{!! BsForm::select('batch_id', array_combine($batchIds, $batchIds)) !!}
	</div>
	<div class="form-group">
		{!! BsForm::submit('Send email', ['class' => 'confirmed', 'data-confirm' => "Are you sure you want to send this email?"]) !!}
	</div>
</fieldset>
{!! BsForm::close() !!}

@include('email-sender.test-send-modal')
