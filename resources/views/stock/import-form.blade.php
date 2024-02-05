<?php
use App\Models\Supplier;
$suppliers = ['' => 'None'] + Supplier::get()->pluck('name', 'id')->toArray();
$vat_types = ['Margin' => 'Margin', 'Standard' => 'Standard'];
?>
<div class="row">
	<div class="col-md-4">
		<div class="panel panel-default">
			<div class="panel-body">
				@if (session('stock.csv_errors'))
					<div class="alert alert-danger">
						There were errors in the uploaded CSV file. Please see the list below and fix the problems before re-uploading.
					</div>
					@foreach (session('stock.csv_errors') as $rowData)
						<h5 class="text-danger">Row {{ $rowData['rowIdx'] + 1 }}</h5>
						@foreach ($rowData['errors']->all() as $error)
							<p class="text-danger">- {{ $error }}</p>
						@endforeach
					@endforeach
				@endif

				{!! Form::open(['route' => 'stock.import', 'files' => true]) !!}
				<div class="form-group">
					{!! Form::file('csv', ['accept' => '.csv']) !!}
				</div>
				<div class="checkbox">
					<label>
						{!! Form::checkbox('mark_in_stock', old('mark_in_stock'), true) !!} Mark in Stock
					</label>
				</div>
				<div class="form-group">
					{!! BsForm::groupSelect('supplier_id', $suppliers, null, [],['label' => 'Supplier']) !!}
				</div>
				<div class="form-group">
					{!! BsForm::select('vat_type', $vat_types, null, ['required' => 'required']) !!}
				</div>

					<div class="form-group">
						{!! BsForm::select('ps_model',[''=>'Please Select P/S Model','1'=>'Yes','0'=>'No'], null, ['required' => 'required']) !!}
					</div>
				<div class="form-group">
					{!! Form::submit('Import', ['class' => 'btn btn-primary']) !!}
				</div>
				{!! Form::close() !!}
			</div>
		</div>
	</div>
</div>
