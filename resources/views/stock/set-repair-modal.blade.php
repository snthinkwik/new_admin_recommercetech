<div id="stock-set-in-repair-modal" class="modal fade" tabindex="-1" role="dialog">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title">Set status to "In Repair"</h4>
			</div>
			<div class="modal-body">
				<div class="row">
					<div class="col-md-6">
						{!! BsForm::open(['route' => 'stock.change-status']) !!}
							{!! BsForm::groupText('ref', null, ['placeholder' => 'IMEI, serial or 3rd-party ref'], ['label' => false]) !!}
							{!! BsForm::groupSubmit('Set status') !!}
							{!! BsForm::hidden('status', \App\Models\Stock::STATUS_REPAIR) !!}
						{!! BsForm::close() !!}
					</div>
				</div>
				<div class="info"></div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
			</div>
		</div>
	</div>
</div>
