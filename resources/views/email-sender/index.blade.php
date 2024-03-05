@extends('app')

@section('title', 'Emails')

@section('content')

	<div class="container">
		@include('messages')
		<a class="btn btn-primary btn-sm" href="{{ route('emails.create-form') }}">Create New Email</a>
		<a class="btn btn-sm btn-default" href="{{ route('emails.drafts') }}">Drafts</a>
		@include('email-sender.list')
		<div id="email-pagination-wrapper">{!! $emails->render() !!}</div>
	</div>

@endsection