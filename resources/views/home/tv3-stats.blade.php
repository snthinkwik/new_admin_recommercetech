@extends('app')

@section('title', 'TV3 Stats')

@section('content')

	<div class="container">

		<div class="row">
			<div id="tv3-stats-wrapper">
				@include('home.tv3-stats-list')
			</div>
		</div>

	</div>

@endsection