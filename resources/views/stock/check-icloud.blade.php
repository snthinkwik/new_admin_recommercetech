@extends('app')

@section('title', 'Check Status')

@section('content')
	<div class="container">
		@include('messages')
		<p><a class="btn btn-default" href="{{ session('stock.last_url') ?: route('stock') }}">Back to list</a></p>
		<div class="row">
			<div class="col-md-4">
				{{--{!! BsForm::open(['route' => 'stock.receive'], ['id-and-prefix' => 'stock-receive']) !!}--}}
				@if($checkCount<5 || Auth::user()->type =='admin')
					{!! BsForm::open(['route' => 'stock.check-icloud'], ['id-and-prefix' => 'check-cloud']) !!}
					{!!
						BsForm::groupText(
							'imei',
							null,
							['placeholder' => 'IMEI'],
							['label' => 'iCloud Check']
						)
					!!}
					{!! BsForm::submit('Check', ['class'=>'btn-block']) !!}
					{!! BsForm::close() !!}
					<p>Checks this month: {{ $checkCount }} @if(Auth::user()->type != 'admin') - ({{ 5-$checkCount }} left) @endif</p>
				@else
					<div class="alert alert-danger">
						<p><b>You've already used your 5 checks.</b></p>
						<p>Try next month.</p>
					</div>
				@endif
			</div>
			@if($latestChecks)
				<div class="col-md-7 col-md-offset-1">
					<p>Your checks within last 15 minutes</p>
					<table class="table table-stripped table-responsive">
						<tr>
							<th></th>
							<th>IMEI</th>
							<th>Name</th>
							<th>Capacity</th>
							<th>Colour</th>
							<th>Locked</th>
							<th>Date</th>
						</tr>
						@foreach($latestChecks as $f)
							<tr>
								<td>
									@if($f->status == 'Error')
										<i class="fa fa-remove"></i>
									@elseif($f->status == 'New')
										<i class="fa fa-hourglass"></i>
									@elseif($f->status == 'Finished')
										<i class="fa fa-check-square-o"></i>
									@endif
								</td>
								<td>{{ $f->imei }}</td>
								@if($f->status == 'Finished')
									<td>{{ str_replace('"','',json_encode(unserialize($f->report)['name'])) }}</td>
									<td>{{ json_encode(unserialize($f->report)['capacity']) }}GB</td>
									<td>{{ str_replace('"','',json_encode(unserialize($f->report)['colour'])) }}</td>
									<td>{{ json_encode(unserialize($f->report)['locked']) == 'true' ? 'Locked':'Unlocked'}}</td>
								@else
									<td colspan="4" class="text-center">
										@if($f->status == 'Error')
											<p class="text-danger">Something went wrong</p>
										@elseif($f->status == 'New')
											<p class="text-info">Report should be available in a minute</p>
								@endif
								@endif
								<td>{{ date('h:i d/m/Y', strtotime($f->checked_at)) }}</td>
							</tr>
						@endforeach
					</table>
				</div>
			@endif
		</div>
	</div>
@endsection