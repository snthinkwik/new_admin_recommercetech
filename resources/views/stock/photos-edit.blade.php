@extends('app')

@section('title', 'Photos')

@section('content')

	<div class="container">
		{!! Form::open(['route' => ['stock.photos'], 'method' => 'get', 'class' => 'form-inline mb15']) !!}
			<div class="form-group">
				<div class="input-group">
					<div class="input-group-addon">RCT</div>
					{!! Form::text('our_ref', str_pad($request->our_ref, 3, 0, STR_PAD_LEFT), ['id' => 'photos-our-ref', 'class' => 'form-control']) !!}
				</div>
			</div>
			{!! Form::submit('Use this ref', ['class' => 'btn btn-primary']) !!}
		{!! Form::close() !!}

		@if ($item)
			<p>Editing photos for <a href="{{ route('stock.single', $item->id) }}">{{ $item->long_name }}</a></p>
			<p>
				<span class="btn btn-success fileinput-button">
					<i class="glyphicon glyphicon-plus"></i>
					<span>Select files...</span>
					<input id="stock-photos-upload" type="file" name="photos[]" multiple>
				</span>
			</p>
			<hr>
			<div id="stock-photos-progress" class="progress hide">
				<div class="progress-bar progress-bar-success"></div>
			</div>
			<div id="stock-photos" class="row"></div>
		@endif
	</div>

	<div id="photo-tpl" class="photo hide col-sm-2" aria-hidden="true">
		<div class="thumbnail">
			<a href="" target="_blank" class="img-link"><img src=""></a>
			<div class="caption">
				<a href="javascript:" class="delete btn btn-danger">Delete</a>
			</div>
		</div>
	</div>

@endsection

@section('pre-scripts')
	@if ($item)
		<script>
			Data.stock.currentId = {{ $item->id }};
		</script>
	@endif
@endsection