@extends('app')

@section('title', 'Notifications')

@section('content')

	<div class="container">

		<h2>Notifications <i class="fa fa-bell"></i></h2>

		@include('messages')

		<div class="row">
			<div class="col-md-6">
				<table class="table table-hover table-bordered">
					<tr>
						<th>Orders Paid Awaiting Dispatch</th>
						<td><a href="{{ route('sales') }}">{{ $data->orders_paid_awaiting_dispatch }}</a></td>
					</tr>
					<tr>
						<th>Click2unlock Balance</th>
						<td @if($data->balance < 250) class="text-danger" @endif>{{ money_format(config('app.money_format'), $data->balance) }}</td>
					</tr>
					<tr>
						<th>Users to Add to What's App</th>
						<td><a href="{{ route('admin.users.whats-app-users') }}">{{ $data->whats_app_users }}</a></td>
					</tr>
					<tr>
						<th>Batch Offers Not Seen</th>
						<td>{{ $data->batch_offers_not_seen }} @if(count($data->batch_offers_not_seen_list)) : @foreach($data->batch_offers_not_seen_list as $batch) <a href="{{ route('batches.deal-sheet', ['id' => $batch->batch_id]) }}">#{{ $batch->batch_id }}</a> @endforeach  @endif</td>
					</tr>
					<tr>
						<th>Unlocks</th>
						<td>{{ count($data->notifications_unlocks) }}</td>
					</tr>
				</table>
			</div>
			@if(count($data->notifications_unlocks))
				<div class="col-md-12 mt-5">
					<div class="panel panel-default">
						<div class="panel-heading">Unlocks Notifications</div>
						<div class="panel-body" id="unlocks-table-wrapper">
							{!! BsForm::open(['method' => 'post', 'route' => 'unlocks.retry-place-unlock-order-cron']) !!}
								{!! BsForm::groupSubmit('Retry Place Unlock Order Cron', ['class' => 'confirmed', 'data-confirm' => 'Cron will be started']) !!}
							{!! BsForm::close() !!}
							<hr/>
							@foreach($data->notifications_unlocks as $unlockId => $notificationUnlock)
								<li class="list-group-item">{!! $notificationUnlock !!}
								{!! BsForm::open(['route' => 'unlocks.fail', 'class' => 'pull-right']) !!}
									{!! BsForm::hidden('id', $unlockId) !!}
									{!! BsForm::submit('Fail', ['class' => 'btn btn-default btn-sm fail']) !!}
								{!! BsForm::close() !!}</li>
							@endforeach
						</div>
					</div>
				</div>
			@endif
		</div>

	</div>
	@include('unlocks.fail-reason-modal')
@endsection
