<style>
	textarea.form-control{
		height: 34px !important;
		resize: none;
	}

</style>

@extends('app')

@section('title', $accessory->name)

@section('content')

	<div class="container">

		<a class="btn btn-default" href="{{ route('sales.accessories') }}">Back to list</a>

		<h2>{{ $accessory->name }}</h2>

		@include('messages')

		<div class="row">

			<div class="col-12">

				{{-- Details Start--}}
				<div class="panel panel-default">
					<div class="panel-heading">
						Details
					</div>
					<div class="panel-body">
						{!! BsForm::model($accessory, ['method' => 'post','files' => 'true', 'route' => 'sales.accessories.update']) !!}
							{!! BsForm::hidden('id', $accessory->id) !!}
							<div class="row">
								<div class="form-group col-sm-12 col-md-12 mb-0">
									{!! BsForm::groupText('name', null, ['required' => 'required']) !!}
								</div>

								<div class="form-group col-sm-12 col-md-12 mb-0">
									{!! BsForm::groupText('sku', null) !!}
								</div>

								<div class="form-group col-sm-12 col-md-12 mb-0">
									{!! BsForm::groupText('quantity', null, ['required' => 'required']) !!}
								</div>

								<div class="form-group col-sm-12 col-md-12">
									{!! BsForm::hidden('old_image', $accessory->image) !!}
									<label>Image</label>
									{!! BsForm::groupFile('image') !!}
									@if($accessory->image)
										<img class="img img-responsive" src="{{ $accessory->image }}" height="100px" width="100px">
									@endif
								</div>
								<div class="form-group col-sm-12 text-center w-100">
									{!! BsForm::groupSubmit('Update Accessory', ['class' => 'btn-block']) !!}
								</div>
							</div>

						{!! BsForm::close() !!}

					</div>
				</div>
				{{-- Details End--}}

			</div>

		</div>

	</div>
	
@endsection