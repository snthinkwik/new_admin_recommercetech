<?php
use App\Models\Email;
?>
<div data-status="{{ $email->status }}">
	<div>{{ $email->status }}</div>
	@if ($email->status !== Email::STATUS_ERROR)
		<div class="text-muted"><small>{{ $email->status_details }}</small></div>
	@elseif($email->status === Email::STATUS_ERROR && $email->sent_to)
		<div class="text-muted"><small>EmailsTracking: Sent to {{ $email->sent_to }} users</small></div>
	@endif
</div>
