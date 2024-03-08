<?php
use Illuminate\Support\Facades\Request;
$typeArray = ['' => 'All', 'sent' => 'Sent', 'inbox' => 'Inbox'];
?>
{!! BsForm::open(['id' => 'emails-search-form', 'class' => 'form-inline mb15', 'method' => 'get']) !!}
<div class="form-group">
	{!! BsForm::text('term', Request::input('term'), ['id' => 'emails-search-term', 'placeholder' => 'Search', 'size' => 30]) !!}
	{!! BsForm::select('type', $typeArray, Request::input('type')) !!}
</div>
{!! BsForm::close() !!}