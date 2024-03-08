<?php
use App\Models\Stock;
$grades = ['' => ''] + Stock::getAvailableGradesWithKeys();
//$networks = ['' => ''] + Stock::getAvailableNetworksWithKeys();
$networksList = Stock::getAllAvailableNetworks();
?>
{!! BsForm::open(['method' => 'get', 'id' => 'ready-for-sale-search-form', 'class' => 'spinner form-inline mb10']) !!}
	<div class="form-group">
		<div class="input-group">
			<span class="input-group-addon">Grade</span>
			{!! BsForm::select('grade', $grades, Request::get('grade')) !!}
		</div>
	</div>
	<div class="form-group">
		<div class="input-group">
			<span class="input-group-addon">Network</span>
			<select class="network-select2 form-control" name="network">
                            <option value=""></option>
                            @foreach($networksList as $country => $networkArr)
                                @if(!empty($networkArr))
                                    <optgroup label="{{$country}}">
                                        @foreach($networkArr as $network)
                                            <option value="{{$network}}">{{$network}}</option>
                                        @endforeach
                                    </optgroup>
                                @endif
                            @endforeach
			</select>

		</div>
	</div>
	<div class="form-group">
		<div class="input-group">
			<span class="input-group-addon">Name</span>
			{!! BsForm::text('term', Request::get('term')) !!}
		</div>
	</div>
	<div class="form-group">
		<div class="input-group">
			<span class="input-group-addon">SKU</span>
			{!! BsForm::text('new_sku', Request::get('new_sku')) !!}
		</div>
	</div>
	<div class="form-group">
		<div class="input-group">
			<span class="input-group-addon">Show Missing SKU's</span>
			{!! BsForm::select('show_missing_sku', ['' => 'All', 1 => 'Yes', 0 => 'No'], Request::get('show_missing_sku')) !!}
		</div>
	</div>
{!! BsForm::close() !!}
