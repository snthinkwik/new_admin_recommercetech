<?php
use App\Models\Sale;
$couriers = Sale::getAvailableCouriersWithKeys();
$courier = Sale::COURIER_DPD;
?>
<div id="tracking-number-modal" class="modal fade" tabindex="-1" role="dialog" data-keyboard="false" data-backdrop="static">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title">Add tracking number</h4>
			</div>
			<div class="modal-body">

				{!! BsForm::open(['route' => 'sales.tracking-number']) !!}
					{!! BsForm::hidden('sale_id') !!}
					{!! BsForm::groupText('number', null, ['placeholder' => 'Tracking number']) !!}
					{!! BsForm::groupSelect('courier', $couriers, $courier) !!}
					{!! BsForm::groupSubmit('Save tracking number') !!}
				{!! BsForm::close() !!}

			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
			</div>
		</div>
	</div>
</div>
