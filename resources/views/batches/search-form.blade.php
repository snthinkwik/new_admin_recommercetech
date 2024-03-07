<?php
use App\Batch;

$statuses = ['all' => 'All'] + Batch::getAvailableStatuses(true);
?>
{!! BsForm::open(['method' => 'get', 'id' => 'universal-search-form', 'class' => 'spinner form-inline mb10 ib']) !!}
	<div class="form-group">
		<div class="input-group">
			<span class="input-group-addon">Status</span>
			{!! BsForm::select('status', $statuses, Request::get('status') ? : Batch::STATUS_FOR_SALE) !!}
		</div>
	</div>
{!! BsForm::close() !!}