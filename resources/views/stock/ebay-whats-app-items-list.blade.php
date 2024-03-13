<?php
$fieldsFormatted = ['average_purchase_price', 'average_sale_price'];
?>
	<div>
		@foreach($items as $item)
			{{ $item->quantity }}x @foreach($requestedFields as $index => $field) {{ $index != 1 ? "-":null }} {{ in_array($field, $fieldsFormatted) ? money_format($item->{$field} ) : $item->{$field} }} @endforeach<br/>
		@endforeach
	</div>
<textarea class="batch-summary-textarea" style="height:0; width:0;">
@foreach($items as $item)
{{ $item->quantity }}x @foreach($requestedFields as $index => $field) {{ $index != 1 ? "-":null }} {{ $item->{$field} }} @endforeach
@endforeach
</textarea>
