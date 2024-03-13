@extends('app')

@section('title', 'Stock Take Scanner')

@section('content')

	<div class="container ">

		<div class="row">
			<div class="col-md-6 col-md-offset-3 mt50">
				<div id="stock-take-scanner-results" class="text-center"><h1>&nbsp;</h1></div>

				{!! BsForm::open(['route' => 'stock-take.scanner', 'id' => 'stock-take-scanner-form', 'class' => 'mt50']) !!}
				<div class="form-group">
					{!! BsForm::text('ref', null, ['required' => 'required', 'autofocus' => 'autofocus']) !!}
				</div>
				{!! BsForm::close() !!}
			</div>
		</div>

	</div>

@endsection