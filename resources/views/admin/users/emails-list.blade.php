@if(count($emails))
	<br/>
	@foreach($emails as $email)
		<div class="panel panel-{{ $email->type == "Sent" ? "default":"info"  }}">
			<div class="panel-heading">
				<div class="row">
					<div class="col-md-6">
						<b>Date:</b>
						@if($email->email_date)
							{{ gmdate('jS F Y H:i T', strtotime($email->email_date)) }}
						@else
							{{ gmdate('jS F Y H:i T', strtotime($email->created_at)) }}
						@endif
						<br/>
						<b>Type: </b> {{ $email->type }}
						<br/>
						<b>Subject:</b> {{ $email->subject }}
						<br>
						<b>From: </b> {{ $email->from ? : $email->from_email }} {{ $email->from_name ? " - ": null }} {{ $email->from_name }}
						<br>
						<b>To: </b> {{ $email->to ? : $email->to_email }} {{ $email->to_name ? " - ": null }} {{ $email->to_name }}
					</div>
					<div class="col-md-6">
						@if(count($email->email_webhooks))
							@foreach($email->email_webhooks as $webhook)
								<span class="pull-right"><b>{{ ucfirst($webhook->type) }} {{ gmdate('j M, Y H:i T', strtotime($webhook->event_time)) }}</b></span><br/>
							@endforeach
						@endif
					</div>
				</div>

				<a class="btn btn-{{ $email->type == "Sent" ? "default":"info"  }} btn-sm btn-block" data-toggle="collapse" data-target="#email-{{ $email->id }}">Show Content</a>
			</div>
			<div class="panel-body collapse" id="email-{{ $email->id }}">
				{!! $email->body !!}
			</div>
		</div>
	@endforeach
@endif