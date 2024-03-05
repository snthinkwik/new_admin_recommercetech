<div class="table-responsive">
    <table class="table small  table-text-break">
        <thead>
            <tr id="manual-assignment-sort">
                <th width="40%" name="fee_title">Title</th>
                <th width="10%" name="date">Date</th>
                <th width="15%" name="item_number">Item Number</th>
                <th width="15%" name="fee_type">Fee Type</th>
                <th width="10%" name="amount">Amount</th>
                <th width="10%">Invoiced?</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ManuallyAssignFee as $fees )
            <tr>
                <td>{{$fees->fee_title}}</td>
                <td>{{date('d-m-Y',strtotime($fees->date))}}</td>
                <td>{{$fees->item_number}}</td>
                <td>{{$fees->fee_type}}</td>
                <td>{{$fees->amount}}</td>
                <td>
                    @if(!empty($fees->invoice_number))
                    <i class="fa fa-check text-success" aria-hidden="true"></i>
                    @else
                    <i class="fa fa-times text-danger" aria-hidden="true"></i>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

