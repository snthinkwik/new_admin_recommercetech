@extends('app')

@section('title', 'TV5 Stats')

@section('content')

	<div class="container">

		<div class="row">
			<div id="tv5-stats-wrapper">
				@include('home.tv5-stats-list')
			</div>
		</div>

	</div>

@endsection