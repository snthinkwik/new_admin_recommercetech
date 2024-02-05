<?php
use App\Models\Stock;
?>
<div id="stock-shown-to-modal" class="modal fade" tabindex="-1" role="dialog">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title">Who can see the selected stock</h4>
			</div>
			<div class="modal-body">
				<p class="count-info"></p>
				<div class="row">
					<div class="col-md-6">
						{!! BsForm::open() !!}
							@foreach (Stock::getAvailableShownTo() as $shownTo)
								<div class="radio">
									<label>
										{!! BsForm::radio('shown_to', $shownTo, $shownTo === Stock::SHOWN_TO_NONE) !!}
										{{ $shownTo }}
									</label>
								</div>
							@endforeach
						{!! BsForm::close() !!}
					</div>
				</div>
				<div class="info"></div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
				<button type="button" class="btn btn-primary save">Save</button>
			</div>
		</div>
	</div>
</div>
