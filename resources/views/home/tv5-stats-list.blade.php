<div class="col-md-12">
	<h1 class="big300 text-center">Engineers Totals</h1>
</div>
<div class="col-md-8 col-md-offset-2 text-center">
	<table class="table table-striped">
		<tr>
			<th class="text-center">Engineer</th>
			<th class="text-center">Total Today</th>
			<th class="text-center">Total This Month</th>
			<th class="text-center">All Time Total</th>
			<th class="text-center">Inactive For?</th>
		</tr>
		@foreach($engineers as $engineer)
			<tr>
				<td>{{ $engineer->first_name }}</td>
				<td>{{ $engineer->today }}</td>
				<td>{{ $engineer->month }}</td>
				<td>{{ $engineer->all }}</td>
				<td>{{ $engineer->inactive }}</td>
			</tr>
		@endforeach
	</table>
</div>

<div class="col-md-12">
	<h1 class="big300 text-center">Last 5 devices tested...</h1>
</div>
<div class="col-md-8 col-md-offset-2 text-center">
	<table class="table table-striped">
		<tr>
			<th class="text-center">RCT Ref</th>
			<th class="text-center">Time</th>
			<th class="text-center">Item</th>
			<th class="text-center">Tested By</th>
			<th class="text-center">Grade</th>
		</tr>
		@foreach($recentItems as $recentItem)
			<tr>
				<td>{{ $recentItem->stock->id }}</td>
				<td>{{ $recentItem->updated_at->format('h:i:sA d/m/Y') }}</td>
				<td>{{ $recentItem->stock->name }} {{ $recentItem->stock->capacity_formatted }}</td>
				<td>{{ $recentItem->tested_by }}</td>
				<td>{{ $recentItem->stock->grade }}</td>
			</tr>
		@endforeach
	</table>
</div>