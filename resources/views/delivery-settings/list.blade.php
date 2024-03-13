<div class="table-responsive">
    <table class="table small table-text-break">
        <thead>
            <tr id="ebay-order-sort">
                <th name="carrier">Carrier</th>
                <th name="service_name">Service Name</th>
                <th name="cost">Cost</th>
            </tr>
        </thead>
        <tbody>
            @foreach($deliverySettingsList as $deliverySettings)
            <tr>
                <td>{{$deliverySettings->carrier}}</td>
                <td>{{$deliverySettings->service_name}}</td>
                <td>{{money_format( $deliverySettings->cost)}}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
