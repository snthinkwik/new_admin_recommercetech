<div class="table-responsive">
    <table class="table small table-text-break">
        <thead>
            <tr id="item-sort">
                <th>Owner</th>
                <th>Total no. Orders</th>
                <th>Sales Revenue</th>
                <th>eBay Fees</th>
                <th>eBay Refund</th>
                <th>Delivery fees</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ebayOrders as $ebay)
            <tr>
                <td>{{$ebay->owner}}</td>
                <td>{{$ebay->total_order}}</td>
                <td>{{money_format( $ebay->total_price)}}</td>
                <td>{{money_format(EbayFees::getEbayFees($ebay->owner,$start_date,$end_date)->total_fees)}}</td>
                <td>{{money_format(EbayRefund::getRefund($ebay->owner))}}</td>
                <td>{{money_format(DpdInvoice::getDeliveryFees($ebay->owner) + EbayDeliveryCharges::getDeliveryFees($ebay->owner))}}</td>
            </tr>
            @endforeach
            <tr>
                <td>Unassigned</td>
                <td>{{$ebayOrdersUnassigned[0]->total_order}}</td>
                <td>{{money_format( $ebayOrdersUnassigned[0]->total_price)}}</td>
                <td>{{money_format(EbayFees::getEbayFees($ebay->owner,$start_date,$end_date)->total_fees)}}</td>
                <td>{{money_format(EbayRefund::getRefund())}}</td>
                <td>{{money_format(DpdInvoice::getDeliveryFees() + EbayDeliveryCharges::getDeliveryFees())}}</td>
            </tr>
        </tbody>
    </table>
</div>
