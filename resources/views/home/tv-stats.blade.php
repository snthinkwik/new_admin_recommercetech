@extends('app')

@section('title', 'TV Stats')

@section('content')

	<div class="container">

		<div class="row">
			<div id="tv-stats-wrapper">
				@include('home.tv-stats-list')
			</div>
		</div>

	</div>

@endsection