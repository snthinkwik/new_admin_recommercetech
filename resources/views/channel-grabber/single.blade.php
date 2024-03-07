@extends('app')

@section('title', 'Channel Grabber Update Logs - Details')

@section('content')

	<div class="container">

		<h2>Channel Grabber Update Logs - Details</h2>

		@include('messages')

		<div class="row">
			<div class="col-md-6">
				<div class="panel panel-default">
					<div class="panel-heading">Info</div>
					<div class="panel-body">
						<table class="table table-bordered table-hover">
							<tr>
								<th>Date</th>
								<td>{{ $log->created_at->format('d/m/y H:i:s') }}</td>
							</tr>
							<tr>
								<th>Cron</th>
								<td>{{ $log->cron }}</td>
							</tr>
							<tr>
								<th>SKU Qty</th>
								<td>{{ $log->sku_qty }}</td>
							</tr>
							<tr>
								<th>Found</th>
								<td>{{ $log->found_qty }}</td>
							</tr>
							<tr>
								<th>Not Found</th>
								<td>{{ $log->not_found_qty }}</td>
							</tr>
							<tr>
								<th>Updated</th>
								<td>{{ $log->updated_qty }}</td>
							</tr>
							<tr>
								<th>Update Error</th>
								<td>{{ $log->update_error_qty }}</td>
							</tr>
						</table>
					</div>
				</div>
			</div>
			<div class="col-md-6">
				<div class="panel panel-default">
					<div class="panel-heading">Details</div>
					<div class="panel-body">
						<div class="panel-group">
							@if(is_object($logDetails))
								@if(count($logDetails->found))
									<div class="panel panel-default">
										<div class="panel-heading" data-toggle="collapse" data-target="#found"><i class="fa fa-bars"></i> Found <span class="badge">{{ count($logDetails->found) }}</span></div>
										<div class="panel-body collapse" id="found">
											@foreach($logDetails->found as $sku)
												{{ $sku }}<br/>
											@endforeach
										</div>
									</div>
								@endif
								@if(count($logDetails->not_found))
									<div class="panel panel-default">
										<div class="panel-heading" data-toggle="collapse" data-target="#not-found"><i class="fa fa-bars"></i> Not Found <span class="badge">{{ count($logDetails->not_found) }}</span></div>
										<div class="panel-body collapse" id="not-found">
											@foreach($logDetails->not_found as $sku)
												{{ $sku->sku }} @if(count($sku->items)) @foreach($sku->items as $itemId)<a href="{{ route('stock.single', ['id' => $itemId]) }}">#{{ $itemId }}</a> @endforeach @endif<br/>
											@endforeach
										</div>
									</div>
								@endif
								@if(count($logDetails->updated))
									<div class="panel panel-default">
										<div class="panel-heading" data-toggle="collapse" data-target="#updated"><i class="fa fa-bars"></i> Updated <span class="badge">{{ count($logDetails->updated) }}</span></div>
										<div class="panel-body collapse" id="updated">
											<table class="table table-bordered table-hover">
												<tr>
													<th>SKU</th>
													<th>Old</th>
													<th>New</th>
												</tr>
												@foreach($logDetails->updated as $log)
													<tr>
														<td>{{ $log->sku }}</td>
														<td>{{ $log->old }}</td>
														<td>{{ $log->new }}</td>	
													</tr>
												@endforeach
											</table>
										</div>
									</div>
								@endif
								@if(count($logDetails->not_updated_same_amount))
									<div class="panel panel-default">
										<div class="panel-heading" data-toggle="collapse" data-target="#not-updated-same-amount"><i class="fa fa-bars"></i> Not Updated - Same Amount <span class="badge">{{ count($logDetails->not_updated_same_amount) }}</span></div>
										<div class="panel-body collapse" id="not-updated-same-amount">
											@foreach($logDetails->not_updated_same_amount as $sku)
												{{ $sku }}<br/>
											@endforeach
										</div>
									</div>
								@endif
								@if(count($logDetails->update_error))
									<div class="panel panel-default">
										<div class="panel-heading" data-toggle="collapse" data-target="#update-error"><i class="fa fa-bars"></i> Update Error <span class="badge">{{ count($logDetails->update_error) }}</span></div>
										<div class="panel-body collapse" id="update-error">
											@foreach($logDetails->update_error as $sku)
												{{ $sku }}<br/>
											@endforeach
										</div>
									</div>
								@endif
							@endif
						</div>
					</div>
				</div>
			</div>
		</div>

	</div>

@endsection