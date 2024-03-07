@extends('app')

@section('title', 'TV4 Stats')

@section('content')

	<div class="container">

		<div class="row">
			<div id="tv4-stats-wrapper">
				@include('home.tv4-stats-list')
			</div>
		</div>

	</div>

@endsection