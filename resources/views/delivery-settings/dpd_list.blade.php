<div class="table-responsive">
    <table class="table small table-text-break">
        <thead>
            <tr id="dpd-sort">
                <th><input type="checkbox" id="checkAll"/></th>
                <th name="date">Date</th>
                <th name="consignment_number">Consignment Number</th>
                <th name="parcel_number">Parcel Number</th>
                <th name="product_description">Product Description</th>
                <th name="service_description">Service Description</th>
                <th name="delivery_post_code">Delivery Post Code</th>
                <th name="cost">Cost</th>
                <th>Owner</th>
                <th>Matched?</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dpdList as $dpd)
            <tr>
                <td><input type="checkbox" name="owner" value="{{$dpd->id}}" data-id="{{$dpd->id}}"></td>
                <td>{{date('d-m-Y',strtotime($dpd->date))}}</td>
                <td>{{$dpd->consignment_number}}</td>
                <td>{{$dpd->parcel_number}}</td>
                <td>{{$dpd->product_description}}</td>
                <td>{{$dpd->service_description}}</td>
                <td>{{$dpd->delivery_post_code}}</td>
                <td>{{money_format(config('app.money_format'), $dpd->cost)}}</td>
                <td>{{ $dpd->owner }}</td>
                <td>
                    @if(!is_null($dpd->matched) && $dpd->matched == "N/A")
                    N/A
                    @elseif(!is_null($dpd->matched) && $dpd->matched !== "N/A")
                    <a href="{{route('admin.ebay-orders.view',['id' => $dpd->order->id])}}"> <i class="fa fa-check text-success" aria-hidden="true"></i></a>
                    @else
                    <i class="fa fa-times text-danger" aria-hidden="true"></i>
                    @endif

                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>