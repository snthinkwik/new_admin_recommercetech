@if (!count($emails))
	<div class="alert alert-warning">No emails yet.</div>
@else
	<table id="emails-table" class="table table-striped">
		<thead>
			<tr>
				<th></th>
				<th>Status</th>
				<th>Subject</th>
				<th>Body</th>
				<th>To</th>
				<th>From</th>
				<th>Brand</th>
			</tr>
		</thead>
		<tbody>
			@foreach ($emails as $email)
				<tr data-id="{{ $email->id }}">
					<td>{!! link_to_route('emails.single', '', $email->id, ['class' => 'fa fa-eye']) !!}</td>
					<td class="status">
						@include('email-sender.list-item-status')
					</td>
					<td>{{ $email->subject }}</td>
					<td>{{ str_limit(strip_tags($email->body), 50) }}</td>
					<td>{{ $email->to }} @if($email->option) <small>{{ $email->option_formatted }}</small>@endif</td>
					<td>{{ $email->from_full }}</td>
					<td>{{ $email->brand ? : "Recomm" }}</td>
				</tr>
			@endforeach
		</tbody>
	</table>
@endif