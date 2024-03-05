<?php
$deliverySettingsList = \App\DeliverySettings::all();
?>
{{--@if ($ebayAll)--}}
<table class="table table-striped">
    <thead>
        <tr class="th-pt-0">
            <th width="5%"><input type="checkbox" id="checkAll"/></th>
            <th width="10%">Order Number</th>
            <th width="15%">Sales Record No.</th>
            <th width="35%">Item Name</th>
            <th width="20%">Custom Label</th>
            <th width="10%">Sales Price</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($ebayOrderItems as $orderItem)
        <tr>
            <td><input type="checkbox" name="owner" value="{{$orderItem->id}}" data-id="{{$orderItem->id}}"></td>
            <td>{{ $orderItem['order_id'] }}</td>
            <td>{{ $orderItem['sales_record_number'] }}</td>
            <td>{{ $orderItem['item_name'] }}</td>
            <td>{{ $orderItem['item_sku'] }}</td>
            <td>{{money_format(config('app.money_format'),  $orderItem['individual_item_price'])}}</td>
        </tr>
        @endforeach
    </tbody>
</table>
{{--`@endif`--}}

