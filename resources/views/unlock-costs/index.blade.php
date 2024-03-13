<?php
use App\Network;
$networks = Network::customOrder()->lists('pr_network');
$networks = array_combine($networks, $networks);
?>
@extends('app')

@section('title', "Unlocks Cost")

@section('content')

	<div class="container">
		@include('messages')

		<h2>Unlocks Cost</h2>

		<div class="row">
			<div class="col-md-6">
				<div class="panel panel-default">
					<div class="panel-heading" data-toggle="collapse" data-target="#add-unlock-cost"><i class="fa fa-plus"></i> Add New</div>
					<div class="panel-body collapse" id="add-unlock-cost">
						{!! BsForm::open(['method' => 'post', 'route' => 'unlocks-cost.add']) !!}
							{!! BsForm::groupSelect('network', $networks, null, ['required' => 'required']) !!}
							{!! BsForm::groupNumber('service_id', null, ['min' => 0],['label' => 'CMN Service ID - set as 0 if manually updated']) !!}
							<div class="form-group">
								<div class="input-group">
									<span class="input-group-addon">Cost &pound;</span>
									{!! BsForm::number('cost', null, ['step' => 0.01]) !!}
								</div>
								<p class="text-info">If CMN service id is set, cost will be updated using cron</p>
							</div>
							{!! BsForm::groupSubmit('Save', ['class' => 'btn-block']) !!}
						{!! BsForm::close() !!}
					</div>
				</div>
			</div>
		</div>

		@if(count($unlocksCost)==0)
			<p>Nothing to display.</p>
		@else
			<table class="table table-hover table-bordered">
				<tr>
					<th>Network</th>
					<th>CMN Service ID</th>
					<th>Cost</th>
				</tr>
				@foreach($unlocksCost as $unlockCost)
					<tr>
						<td>{{ $unlockCost->network }}</td>
						<td>{{ $unlockCost->service_id }}</td>
						<td>{{ $unlockCost->cost_formatted }}</td>
					</tr>
				@endforeach
			</table>
		@endif
	</div>

@endsection