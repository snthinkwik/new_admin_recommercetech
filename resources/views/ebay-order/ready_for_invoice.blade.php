<div class="table-responsive">
    <table class="table small table-text-break">
        <thead>
            <tr id="ready-for-invoice-sort">
                <th name="sales_record_number" width="11%">Sales Record No.</th>
                <th width="7%">Sale Date</th>
                <th name="item_name" width="20%">Item Name</th>
                <th name="item_number" width="9%">Item Number</th>
                <th name="item_sku" width="10%">Custom Label</th>
                <th name="quantity" width="7%">Quantity</th>
                <th name="individual_item_price" width="8%">Item Price</th>
                <th name="sale_type" width="8%">Sale Type</th>
                <th width="8%">Order Status</th>
                <th>Ready?</th>
                <th>Invoiced?</th>
            </tr>
        </thead>
        <tbody>
            @foreach($OrderItem as $item)
            <tr>
                <td><a href="{{route('admin.ebay-orders.view',['id'=>$item->order_id])}}" target="_blank"> {{$item->sales_record_number}}</a></td>
                <td>
                    @if(isset($item->order->sale_date))
                    {{date('d-m-Y',strtotime($item->order->sale_date))}}
                    @endif
                </td>
                <td>{{$item->item_name}}</td>
                <td>{{$item->external_id}}</td>
                <td>
                    @if(!is_null($item->stock))
                    <a href="{{route('stock.single',['id'=>$item->stock->id])}}"
                       target="_blank"> {{$item->item_sku}}</a>
                    @else
                    {{$item->item_sku}}
                    @endif
                </td>
                <td>{{$item->quantity}}</td>
                <td>{{ money_format($item->individual_item_price)}}</td>
                <td>{{$item->sale_type}}</td>
                <td>{{ucfirst($item->order->status)}}</td>
                <td>
                    <?php
                    $fee_type = array_column($item['matched_to_item']->toArray(), 'fee_type');
                    $ready = 0;
                    if ($item->sale_type == \App\EbayOrderItems::SALE_TYPE_BUY_IT_NOW) {
                        if (in_array("Final Value Fee", $fee_type)) {
                            $ready = 1;
                        }
                    }

                    if ($item->sale_type == \App\EbayOrderItems::SALE_TYPE_AUCTION) {
                        if (in_array("Final Value Fee", $fee_type) || in_array("Insertion Fee", $fee_type)) {
                            $ready = 1;
                        }
                    }
                    ?>

                    @if($item->order->status == \App\EbayOrders::STATUS_REFUNDED || $item->order->status == \App\EbayOrders::STATUS_CANCELLED || ($ready && !is_null($item->order->paypal_fees) && !empty($item->DpdInvoice->toArray())))
                    <i class="fa fa-check text-success" aria-hidden="true"></i>
                    @else
                    <i class="fa fa-times text-danger" aria-hidden="true"></i>
                    @endif
                </td>
                <td>
                	@if(!$item->invoice_number)
						<i class="fa fa-times text-danger" data-toggle="tooltip" title="Invoice"></i>
					@else
						<a href="{{ route('admin.ebay.invoice', ['id' => $item->invoice_number]) }}" target="_blank" data-toggle="tooltip" title="Invoice"><i class="fa fa-check text-success"></i></a>
					@endif
					@if(!$item->order->fees_invoice_number)
						<i class="fa fa-times text-danger" data-toggle="tooltip" title="Fees Invoice"></i>
					@else
						<a href="{{ route('admin.ebay.invoice-fees', ['id' => $item->order->fees_invoice_number]) }}" target="_blank" data-toggle="tooltip" title="Fees Invoice"><i class="fa fa-check text-success"></i></a>
					@endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
