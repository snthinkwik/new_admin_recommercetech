<table class="table table-striped" id="users-table">
    <thead>
    <tr>
        <th></th>
        <th></th>
        <th>Type</th>
        <th>Company Name</th>
        <th>Name</th>
        <th>Email</th>
        <th>Phone</th>
        <th>Customer ID</th>


        <th>Buy from Us</th>
        <th>Sell to Us</th>
        <th>Interested in Repairs</th>
        <th>Company Status</th>
        <th>VAT Registered</th>
        <th>T&C Signed</th>
        <th>KYC Verified</th>
        <th>Last Order Placed</th>
        <th></th>
    </tr>
    </thead>
    <tbody>
    @foreach ($users as $user)
        <?php
       // $sales =  \App\Sale::with('stock')->where('customer_api_id', $user->invoice_api_id)->orderBy('id', 'desc')->first();



        ?>
        <tr>
            <td>
                <a href="{{ route('admin.users.single', ['id' => $user]) }}">
                    <i class="fa fa-pencil-square-o"></i>
                </a>
            </td>
            <?php
            $invoiceId=$user->invoice_api_id!==''? "-".$user->invoice_api_id :''
            ?>
            <td>{{$user->id.$invoiceId}}</td>
            <td>{{ $user->type }}</td>
            <td>{{ $user->company_name }}</td>
            <td class="name">{{ $user->full_name }}</td>
            <td>{{ $user->email }}</td>


            <td>{{ $user->phone }}</td>
            <td>
                {{ $user->invoice_api_id }}
                @if($user->customer_type) <br/><span class="small text-muted" data-toggle="tooltip" title="Customer Type">{{ $user->customer_type }}</span>@endif
            </td>

            @if(!is_null($user->buying_from_us))
                <td>
                    @if($user->buying_from_us)
                        <div class="badge alert-success">Yes</div>
                    @else
                        <div class="badge alert-danger">No</div>
                    @endif

                </td>
            @else
                <td>-</td>
            @endif

            @if(!is_null($user->sell_to_recomm))

                <td>
                    @if($user->sell_to_recomm)
                        <div class="badge alert-success">Yes</div>
                    @else
                        <div class="badge alert-danger">No</div>
                    @endif
                </td>

            @else
                <td>-</td>
            @endif
            @if(!is_null($user->interested_in_repairs))
                <td>
                    @if($user->interested_in_repairs)
                        <div class="badge alert-success">Yes</div>
                    @else
                        <div class="badge alert-danger">No</div>
                    @endif
                </td>
            @else
                <td><div class="badge alert-danger">No</div></td>
            @endif
            <td>{{$user->company_status}}</td>
            <td>
                @if($user->vat_registered)
                    <div class="badge alert-success">Yes</div>
                @else
                    <div class="badge alert-danger">No</div>
                @endif
            </td>
            <td>
                @if($user->received)
                    <div class="badge alert-success">Yes</div>
                @else
                    <div class="badge alert-danger">No</div>
                @endif

            </td>
            <td>
                @if($user->kyc_verification)
                    <div class="badge alert-success">Yes</div>
                @else
                    <div class="badge alert-danger">No</div>
                @endif

            </td>
            <td>
                Sales
{{--                {{!is_null($sales)?$sales->created_at->format('Y-m-d H:i'):'-'}}--}}
            </td>
            <td>


                {!! BsForm::open(['method' => 'post', 'route' => 'admin.users.remove-user']) !!}
                {!! BsForm::hidden('id',  $user->id) !!}


                {!! BsForm::button('<i class="fa fa-trash"></i>', ['type' => 'submit', 'class' => 'confirmed btn-danger', 'data-confirm' => 'Are you sure you want to delete this Data?']) !!}
                {!! BsForm::close() !!}



            </td>
        </tr>
    @endforeach
    </tbody>
</table>
