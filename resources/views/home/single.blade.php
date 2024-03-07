<?php
use App\Stock;
use App\Colour;

$selectOption = ['' => 'Any'];
$grades = $selectOption + Stock::getAvailableGradesWithKeys();
$networks = ['EE', 'O2', 'Three', 'Unlocked', 'Vodafone', 'Other'];
$networks = $selectOption + array_combine($networks, $networks);
$colours = ['Space Grey', 'Rose Gold', 'Silver', 'Gold', 'White', 'Black'];
$colours = $selectOption + array_combine($colours, $colours);
$capacity = $selectOption + Stock::getAvailableCapacityWithKeys();
?>

@extends('app')

@section('title', $iphone->name)

@section('content')

	<div class="container">

		@include('messages')

		<a class="btn btn-default btn-sm" href="{{ route('home') }}"><i class="fa fa-reply"></i> Back to Products</a>

		<h2>{{ $iphone->name }}</h2>

		<div class="row">
			<div class="col-md-6">
				<img src="{{ asset('img/iphones/'.$iphone->image.'.png') }}" class="img-rounded" alt="{{ $iphone->name }}">
			</div>
			<div class="col-md-6">
				<div class="thumbnail">
					{!! BsForm::open(['class' => 'form-horizontal', 'route' => 'home.single-search', 'method' => 'get', 'id' => 'product-search-form']) !!}
						{!! BsForm::hidden('name', $iphone->name) !!}
						<div class="form-group">
							<label class="col-md-3 control-label">Network</label>
							<div class="col-md-9">
								{!! BsForm::select('network', $networks) !!}
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 control-label">Capacity</label>
							<div class="col-md-9">
								{!! BsForm::select('capacity', $capacity) !!}
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 control-label">Colour</label>
							<div class="col-md-9">
								{!! BsForm::select('colour', $colours) !!}
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 control-label">Grade</label>
							<div class="col-md-9">
								{!! BsForm::select('grade', $grades, Request::input('grade')) !!}
							</div>
						</div>
						<div class="form-group">
							<div class="col-md-9 col-md-offset-3">
								{!! Form::reset('Clear Selection', ['class' => 'btn btn-block btn-xs btn-default']) !!}
							</div>
						</div>
						<div class="form-group">
							<div class="col-md-9 col-md-offset-3 text-center" id="product-search-submit-wrapper">
								{!! BsForm::submit('Search', ['class' => 'btn-block', 'id' => 'product-search-submit']) !!}
							</div>
						</div>
					{!! BsForm::close() !!}

					<div id="result-wrapper">
						@include('home.search-single-result')
					</div>
				</div>
			</div>
		</div>
	</div>

@endsection