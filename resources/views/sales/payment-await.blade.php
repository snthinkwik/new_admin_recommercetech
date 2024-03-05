@extends('app')

@section('title', 'Initiating payment...')

@section('content')

	<div class="container">
		<div class="row">
			<div class="col-md-4 col-md-offset-4">
				<div class="progress">
					<div class="progress-bar progress-bar-striped active" role="progressbar" style="width: 100%">
						Just a moment...
					</div>
				</div>
			</div>
		</div>
	</div>

@endsection

@section('pre-scripts')
	<script>
		Data.sales.awaitingPaymentId = {!! json_encode($sale->id) !!};
	</script>
@endsection