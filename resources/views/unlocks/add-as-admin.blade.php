<?php
use App\Models\Stock;
$networks = Stock::getAdminUnlockableNetworks();
?>
@extends('app')

@section('title', "Add unlocks")

@section('content')

	<div class="container">
		<p>{!! link_to_route('unlocks', "Back to list", null, ['class' => 'btn btn-default']) !!}</p>
		<h1>Add unlocks</h1>
		@if ($imeiMessages)
			@foreach ($imeiMessages as $imeiMessage)
				<p class="p5 bg-{{ $imeiMessage['htmlClass'] }}">{{ $imeiMessage['text'] }}</p>
			@endforeach
		@endif
		{!! BsForm::open(['route' => 'unlocks.add-as-admin', 'id' => 'unlocks-add-form']) !!}
			{!!
				BsForm::groupTextarea(
					'imeis_list',
					old('imeis_list', $imeiList),
					['placeholder' => 'One or more IMEI numbers, separated by new lines, spaces or commas...'],
					['label' => 'Enter IMEI', 'errors_name' => 'imeis', 'errors_all' => true]
				)
			!!}
			<div class="row">
				<div class="col-md-8">
					<div class="row">
						<div class="col-md-6">
							{!! BsForm::hidden('user_id') !!}

							{!!
								BsForm::groupText(
									'user_id_autocomplete', null, ['placeholder' => 'Search for user (optional)', 'class' => 'user-search'], ['label' => 'User']
								)
							!!}
							{!! BsForm::groupSelect('network', array_combine($networks, $networks)) !!}
						</div>
						<div class="col-md-6">
							<div class="form-group">
								{!! Form::label('ebay_user_id', "eBay User ID") !!}
								{!! BsForm::text('ebay_user_id', null, ['placeholder' => 'Optional']) !!}
							</div>
							<div class="form-group">
								{!! Form::label('ebay_user_email', "Email Address") !!}
								{!! BsForm::text('ebay_user_email', null, ['placeholder' => 'Optional']) !!}
							</div>
						</div>
					</div>

					{!! BsForm::groupSubmit('Add') !!}
				</div>
			</div>
		{!! BsForm::close() !!}
	</div>

@endsection
