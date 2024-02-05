@if(!count($events))
	<div class="alert alert-info">Nothing Found</div>
@else
	<table class="table table-hover">
		<tr>
			<th>Email Address</th>
			<th>Events</th>
			<th>Time</th>
			<th>Status</th>
		</tr>
		@foreach($events as $event)
			<tr data-toggle="popover" data-title="{{ $event->user->email }}" data-html="true" data-content="@foreach($event->email_webhooks as $webhook) <p>{{ $webhook->event_time->format("d/m/Y H:i:s") }} - {{ ucfirst($webhook->type) }}</p> @endforeach" data-trigger="click hover" data-placement="top">
				<td><a href="{{ route('admin.users.single', ['id' => $event->user_id]) }}">{{ $event->user->email }}</a></td>
				<td>{{ count($event->email_webhooks) }}</td>
				<td>
					{{ $event->email_webhooks()->first()->event_time->format("d/m/y H:i:s") }}
				</td>
				<td>{{ ucfirst($event->email_webhooks->first()->type) }}</td>
			</tr>
		@endforeach
	</table>
@endif