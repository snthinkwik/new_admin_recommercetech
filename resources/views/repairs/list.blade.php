<?php
    use App\Models\RepairsItems;
?>
@if(!count($repairs))
	<div class="alert alert-info">Nothing Found</div>
@else
	<table class="table table-striped table-condensed">
		<thead>
			<tr id="item-sort">
				<th name="repair_id">Repair Id</th>
				<th name="engineer">Repair Engineer</th>
				<th>Status</th>
				<th>Internal</th>
				<th>External</th>
				<th>Created At</th>
				<th>Close At</th>


			</tr>
		</thead>
		<tbody>
			@foreach($repairs as $repair)


			<tr>
				<td>{{$repair->repair_id}}</td>
				<td>{{$repair->Repairengineer->name}}</td>
				<?php
                $status=RepairsItems::STATUS_OPEN;


                if(count($repair->RepaireItem)){
                   $status= getStatus($repair->RepaireItem);
                }

                ?>

				<td>{{$status }}</td>
				<td><a href="{{ route('repairs.single', ['id' => $repair->id]) }}">{{getCount($repair->id,RepairsItems::TYPE_INTERNAL)>0 ?getCount($repair->id,RepairsItems::TYPE_INTERNAL):'' }}</a></td>
				<td><a href="{{ route('repairs.external.single', ['id' => $repair->id]) }}">{{getCount($repair->id,RepairsItems::TYPE_EXTERNAL) > 0 ? getCount($repair->id,RepairsItems::TYPE_EXTERNAL):''}}</a></td>
				<td>{{ $repair->created_at->format('d/m/y H:i:s') }}</td>

                <?php


                $closeDate='';
                if(count($repair->RepaireItem)){
                  $closeDate= getLastDate($repair->RepaireItem);
                }

                ?>



				<td>{{$closeDate}}</td>



			</tr>
			@endforeach
		</tbody>
	</table>
@endif
