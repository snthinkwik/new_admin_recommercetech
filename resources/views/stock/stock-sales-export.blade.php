@extends('app')

@section('title', 'VAT Margin Sales Export')

@section('content')
	<div class="container">

		@include('messages')

		<h2>VAT Margin Sales Export</h2>

		<div class="row">
			<div class="col-md-12">
				{!! BsForm::open(['method' => 'get', 'route' => 'stock.stock-sales-export', 'class' => 'form-inline']) !!}
					<div class="form-group">
						<div class="input-group">
							<span class="input-group-addon">Select Month</span>
							{!! BsForm::text('month', \Carbon\Carbon::now(), ['class' => 'has-datemonthpicker', 'required' => 'required']) !!}
							<span class="input-group-btn">{!! BsForm::submit('Export') !!}</span>
						</div>
					</div>
				{!! BsForm::close() !!}
			</div>
		</div>
	</div>
@endsection
