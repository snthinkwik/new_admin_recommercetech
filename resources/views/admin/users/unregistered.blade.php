<?php
use Illuminate\Support\Facades\Request;
?>
@extends('app')

@section('title', 'Users')

@section('content')

	<div class="container">

		@include('messages')

		<p>
			<a href="{{ route('admin.users.new-user') }}">Add new</a> |
			<a href="{{ route('admin.users.bulk-add-form') }}">Bulk add Unregistered</a> |
			<a href="{{ route('admin.users') }}">Search Registered</a>
		</p>

		@include('admin.users.search-form')

		<div id="users-wrapper">@include('admin.users.unregistered-list')</div>

		<div id="users-pagination-wrapper">{!! $users->appends(Request::All())->render() !!}</div>

	</div>

@endsection