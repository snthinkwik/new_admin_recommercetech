@if(!count($items))
<div class="alert alert-info">Nothing Found</div>
@else


<table class="table table-hover table-bordered">
    <thead>
        <tr id="item-sort">
            <th name="trg_ref">RCT Ref</th>
            <th name="make">Make</th>
            <th>Model</th>
            <th name="colour">Colour</th>
            <th name="imei">IMEI</th>
            <th width="20%" name="serial">Serial number</th>
            <th name="purchase_price">Purchase Value</th>
            <th name="marked_as_lost">Date Marked as Lost</th>
            <th name="lost_reason">Lost Reason</th>
            <th>Last Activity Date</th>
        </tr>
    </thead>
    <tbody>
        @foreach($items as $item)

        <tr>
            <td><a href="{{ route('stock.single', ['id' => $item->id]) }}">{{ $item->our_ref }}</a></td>
            <td>{{ $item->make }}</td>
            <td>{{ $item->name }}</td>
            <td>{{ $item->colour }}</td>
            <td>{{ $item->imei }}</td>
            <td>{{$item->serial}}</td>
            <td>{{ $item->purchase_price_formatted }}</td>
            <td>
                @if(!is_null($item->marked_as_lost))
                {{ $item->marked_as_lost->format('d/m/Y H:i') }}
                @endif
            </td>
            <td>{{ $item->lost_reason }}</td>
            <td>


                <span  class="tool-tip-disable"

                       @if(count($item->stockLogs))
                           @if($item->stockLogs[0]->user_id =="" || is_null($item->stockLogs[0]->user_id))
                        title="System change"
                        @else
                        title="{{isset($item->stockLogs[0]->user->full_name) ? $item->stockLogs[0]->user->full_name:''}}"
                        @endif
                      @endif

                    rel="tooltip"
                    data-toggle="tooltip">

                    @if(count($item->stockLogs))
                    {{$item->stockLogs[0]['created_at']->format("d M Y H:i:s")}}
                    @endif
                </span>

            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif
