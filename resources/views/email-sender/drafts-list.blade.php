@if (!count($drafts))
	<div class="alert alert-warning">No drafts yet.</div>
@else
	<table id="emails-table" class="table table-striped">
		<thead>
		<tr>
			<th></th>
			<th>Title</th>
			<th>Subject</th>
			<th>Body</th>
			<th>To</th>
			<th>From</th>
			<th>Date</th>
			<th><i class="fa fa-user" data-toggle="tooltip" title="Created By" data-placement="top"></i></th>
			<th><i class="fa fa-remove" data-toggle="tooltip" title="Delete Draft"></i></th>
		</tr>
		</thead>
		<tbody>
		@foreach ($drafts as $draft)
			<tr data-id="{{ $draft->id }}">
				<td><a class="fa fa-envelope" href="{{ route('emails.create-form', ['draft' => $draft->id]) }}"></a></td>
				<td>{{ $draft->title }}</td>
				<td>{{ $draft->subject }}</td>
				<td>{{ str_limit(strip_tags($draft->body), 50) }}</td>
				<td>{{ $draft->to }} @if($draft->option) <small>{{ $draft->option_formatted }}</small> @endif</td>
				<td>{{ $draft->from_full }}</td>
				<td><small data-toggle="tooltip" title="{{ $draft->created_at->format('d-m-Y H:i:s') }}">{{ $draft->created_at->format("d/m/Y") }}</small></td>
				<td><a data-toggle="tooltip" title="{{ $draft->user->full_name }}" href="{{ route('admin.users.single', ['id' => $draft->user_id]) }}" target="_blank"><i class="fa fa-user"></i></a></td>
				<td>
					{!! BsForm::open(['route' => 'emails.delete-draft', 'method' => 'delete']) !!}
					{!! BsForm::hidden('id', $draft->id) !!}
					{!! BsForm::button('<i class="fa fa-remove"></i>',
						['type' => 'submit',
						'class' => 'btn btn-xs btn-danger confirmed',
						'data-toggle' => 'tooltip', 'title' => "Delete Draft", 'data-placement'=>'right',
						'data-confirm' => "Are you sure you want to delete this draft?"])
					!!}

					{!! BsForm::close() !!}
				</td>
			</tr>
		@endforeach
		</tbody>
	</table>
@endif