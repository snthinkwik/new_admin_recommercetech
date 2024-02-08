<div class="table-responsive">
    <table class="table small table-text-break">
        <thead>
            <tr id="refund-ebay-fee-sort">
                <th name="sales_record_number">Sales Record No.</th>
                <th name="refund_amount">Refund Amount</th>
                <th>Owner</th>
                <th name="processed">Processed?</th>
                <th>Created_at</th>
            </tr>
        </thead>
        <tbody>
            @foreach($eBayRefund as $refund)
            <tr>
                <td>
                    <a href="{{route('admin.ebay-orders.view',['id' => $refund->order_id])}}">{{$refund->sales_record_number}}</a>
                </td>
                <td>{{ money_format(config('app.money_format'), $refund->refund_amount) }}</td>

                <td>{{$refund->owner}}</td>
                <td>
                    @if($refund->processed=="No")
                    <i class="fa fa-times text-danger" aria-hidden="true"></i>
                    @else
                    <i class="fa fa-check text-success" aria-hidden="true"></i>
                    @endif
                </td>
                <td>
                    {{date('d-m-Y',strtotime($refund->created_at))}}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>