<?php
$deliverySettingsList = \App\DeliverySettings::all();
?>
@if ($ebayAll)
<table class="table table-striped">
    <thead>
        <tr class="th-pt-0">
            <th width="35%">SKUS</th>
            <th width="20%">Owner</th>
            <th width="20%">Location</th>
            <th width="15%">Shipping Method</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($ebayAll as $ebaySku)
        <tr>
            <td>{{ $ebaySku['sku'] }}</td>
            <td>{{ $ebaySku['owner'] }}</td>

            <td>
                @if($ebaySku['location'])
                {{ $ebaySku['location'] }}
                @endif
                <i data-toggle="collapse" data-target="#unlock-item-name-{{ $ebaySku->id }}"
                   class="fa fa-pencil"></i>
                {!! BsForm::model($ebaySku, ['method' => 'post', 'route' => 'ebay.sku.location', 'class' => 'mt-2 collapse', 'id' => 'unlock-item-name-'.$ebaySku->id]) !!}
                {!! BsForm::hidden('id', $ebaySku->id) !!}
                <div class="form-group">
                    <div class="input-group">
                        {!! BsForm::text('location') !!}
                        <span class="input-group-btn">{!! BsForm::button('<i class="fa fa-check"></i>', ['type' => 'submit']) !!}
                        </span>
                    </div>
                </div>
                {!! BsForm::close() !!}
            </td>           
            <td>
                <select name="delivery-settings" data-item-id="{{$ebaySku->id}}" class="shipping-update shipping-select2" style="width: 100%;">
                    <option value="">Select Shipping Method</option>
                    @foreach($deliverySettingsList as $deliverySettings)
                    <option value="{{$deliverySettings->id}}" @if($deliverySettings->id==$ebaySku->shipping_method) selected @endif>{{$deliverySettings->carrier.' - '. $deliverySettings->service_name}}</option>
                    @endforeach
                </select>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

@section('scripts')
<script>
    $(document).ready(function () {
        $('.owner-select2').select2({
            placeholder: "Select Owner",
        });
        $('.shipping-select2').select2({
            placeholder: "Select Shipping Method",
            width: 'resolve'
        });
        $('.shipping-update').on('change', function () {
            var item_id = $(this).attr("data-item-id");
            var shippingMethod = $(this).val();

            $.ajax({
                url: "{{ route('ebay.update.shipping-method') }}",
                method: 'post',
                data: {
                    item_id: item_id,
                    shipping_method: shippingMethod
                },
                success: function (result) {
                    $("#message").show();
                    setTimeout(function () {
                        window.location.reload(1);
                    }, 700);

                }
            });

        });
    });


</script>



@endsection