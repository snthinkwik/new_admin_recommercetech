<script>
	@if($type == 'order')
		window.top.paymentCompleteOrder = true;
	@elseif($type == 'custom_payment')
		window.top.paymentCompleteCustomPayment= true;
	@else
		window.top.paymentCompleteSale = true;
	@endif
</script>
