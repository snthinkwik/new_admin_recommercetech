<?php
use Illuminate\Support\Facades\Request;
?>
@extends('app')

@section('title', 'Users')

@section('content')

    <div class="container-fluid">

        @include('messages')

        <p>
{{--            <a href="{{ route('admin.users.new-user') }}">Add new</a> |--}}
{{--            <a href="{{ route('admin.users.bulk-add-form') }}">Bulk add Unregistered</a> |--}}
{{--            <a href="{{ route('admin.users.unregistered') }}">Search Unregistered</a> |--}}
{{--            <a href="{{ route('admin.users.whats-app-users') }}">What's App Users</a> |--}}
{{--            <a href="{{ route('admin.users.customers-with-balance') }}">Customers with Balance</a> |--}}
{{--            <a href="{{ route('admin.users.recommercetech-users') }}">Recommercetech Users</a> |--}}
{{--            <a href="{{ route('admin.users.export', ['option' => 'whats_app_users']) }}">Export What's App Users</a>--}}
        </p>

        @include('admin.users.search-form')

        <div id="users-wrapper">@include('admin.users.list')</div>

        <div id="users-pagination-wrapper">{!! $users->appends(Request::All())->render() !!}</div>

    </div>

@endsection

@section('nav-right')
    <div class="navbar-form navbar-right pr0">
{{--        <div class="btn-group">--}}
{{--            <a href="{{ route('admin.users.export') }}" class="btn btn-default" data-toggle="tooltip" data-placement="bottom" title="Users only">Export XLS</a>--}}
{{--            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">--}}
{{--                <span class="caret"></span>--}}
{{--                <span class="sr-only">Toggle Dropdown</span>--}}
{{--            </button>--}}
{{--            <ul class="dropdown-menu">--}}
{{--                <li><a href="{{ route('admin.users.export', ['option' => 'marketing_enabled']) }}">Marketing Emails Enabled</a></li>--}}
{{--                <li><a href="{{ route('admin.users.export', ['option' => 'whats_app_users']) }}">What's App Users</a></li>--}}
{{--            </ul>--}}
{{--        </div>--}}
    </div>
@endsection
