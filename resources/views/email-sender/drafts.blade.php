@extends('app')

@section('title', 'Emails - Drafts')

@section('content')

	<div class="container">
		@include('messages')
		<a class="btn btn-sm btn-primary" href="{{ route('emails.create-form') }}">Create new Email</a>
		@include('email-sender.drafts-list')
		<div id="email-drafts-pagination-wrapper">{!! $drafts->render() !!}</div>
	</div>

@endsection