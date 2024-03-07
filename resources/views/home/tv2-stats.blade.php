@extends('app')

@section('title', 'TV2 Stats')

@section('content')

	<div class="container">

		<div class="row">
			<div id="tv2-stats-wrapper">
				@include('home.tv2-stats-list')
			</div>
		</div>

	</div>

@endsection