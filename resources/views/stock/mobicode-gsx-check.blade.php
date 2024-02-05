@if(Auth::user()->type === 'admin')
<?php
	$validIMEI = true;
	if(!$item->imei) {
		$validIMEI = false;
		$reason = "GSX check not possible because the device doesn't have the IMEI specified.";
	} elseif(!in_array(strlen($item->imei), [15,16])) {
		$reason = "Invalid IMEI length.";
		$validIMEI = false;
	}
?>
	<div class="col-md-12">
		{!! BsForm::open(['route' => 'mobicode.gsx-check', 'id' => 'mobicode-gsx-check', 'method' => 'post']) !!}
		{!! BsForm::hidden('stock_id', $item->id) !!}
		{!! BsForm::submit('GSX Check',[!$validIMEI ? 'disabled' : null,'class'=>'btn-block']) !!}
		@if (!$validIMEI && $reason)
			<div class="help-block small">{{ $reason }}</div>
		@endif
		{!! BsForm::close() !!}
	</div>

	@if (isset($showDivider) && $showDivider)
		<hr>
	@endif
@endif