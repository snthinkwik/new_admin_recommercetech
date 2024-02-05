<div class="modal fade" id="save-draft-modal" tabindex="-1" role="dialog" aria-labelledby="save-draft-modal-title">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title" id="save-draft-modal-title">Save Draft</h4>
			</div>
			<div class="modal-body">
				{!! BsForm::text('title', null, ['placeholder' => 'Draft Title'], ['label' => false]) !!}
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
				<button type="button" class="btn btn-primary send">Save</button>
			</div>
		</div>
	</div>
</div>