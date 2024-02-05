<div class="modal fade" id="test-send-modal" tabindex="-1" role="dialog" aria-labelledby="test-send-modal-title">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title" id="test-send-modal-title">Test send</h4>
			</div>
			<div class="modal-body">
				{!! BsForm::text('email', null, ['placeholder' => 'Recipient email address'], ['label' => false]) !!}
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
				<button type="button" class="btn btn-primary send">Send</button>
			</div>
		</div>
	</div>
</div>