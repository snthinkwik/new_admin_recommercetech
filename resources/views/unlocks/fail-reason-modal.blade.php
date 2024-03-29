<div id="unlock-fail-reason-modal" class="modal fade" tabindex="-1" role="dialog">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
				<h4 class="modal-title">Reason</h4>
			</div>
			<div class="modal-body">
				{!! BsForm::open() !!}
					{!! BsForm::textArea('reason', null, ['rows' => 3, 'placeholder' => 'Please enter reason why this unlock failed.']) !!}
				{!! BsForm::close() !!}
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
				<button type="button" class="btn btn-danger fail">Fail unlock</button>
			</div>
		</div>
	</div>
</div>