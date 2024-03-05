
New Count:{{$phoneCount}}
<table class="table" border="1">

    <thead>
    <tr id="item-sort">
        <th name="sku">stock_id</th>
        <th name="type">status</th>
        <th name="make">imei</th>
        <th>Date</th>

    </tr>
    </thead>
    <tbody>
    @foreach ($phonecheck as $item)
        <tr>
            <td >{{$item->stock_id}}</td>
            <td >{{$item->status}}</td>
            <td >{{$item->imei}}</td>
            <td  align="center">{{$item->created_at}}</td>

        </tr>

    @endforeach
    </tbody>
</table>

<div id="ebay-order-pagination-wrapper">{!! $phonecheck->appends(Request::all())->render() !!}</div>