<?php
use Illuminate\Support\Facades\Request;
?>
@extends('app')

@section('title', $user->full_name)

@section('content')
	<div class="container">
		@include('messages')

		<p><a href="{{ route('admin.users.single', ['id' => $user->id]) }}" class="btn btn-default">Back to user</a></p>
		<h4>{{ $user->full_name }} - Emails</h4>
		<a class="btn btn-default btn-block" data-toggle="collapse" data-target="#new_email">New Email</a>
		<div class="panel panel-default collapse" id="new_email">
			<div class="panel-body">
				<div class="row">
					<div class="col-md-6">
						@include('admin.users.email-form')
					</div>
					<div class="col-md-6">
						<p>Email preview:</p>
						<iframe id="email-user-preview"></iframe>
					</div>
				</div>
			</div>
		</div>
		<br/>
		@include('admin.users.emails-search-form')

		<div id="emails-wrapper">@include('admin.users.emails-list')</div>

		<div id="emails-pagination-wrapper">{!! $emails->appends(Request::All())->render() !!}</div>

	</div>

@endsection