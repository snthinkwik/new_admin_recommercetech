<?php
use Carbon\Carbon;
?>


<a class="btn btn-default btn-block" data-toggle="collapse" data-target="#sales_table">Orders <span class="badge">@if(!is_null($sales)){{ count($sales) }}@endif</span></a>
<div class="panel panel-default collapse" id="sales_table">
    <div class="panel-body">


        @if(!is_null($sales))
            <table class="table table-striped table-condensed">
                <thead>
                <tr>
                    <th>Details</th>
                    <th>Date</th>
                    <th>No. Days Old</th>
                    <th>Item count</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Invoice</th>
                </tr>
                </thead>
                <tbody>
                @foreach($sales as $sale)
                    <tr>
                        <td><a href="{{ route('sales.single', ['id' => $sale->id]) }}"><i class="fa fa-eye"></i> Details</a></td>
                        <td>{{ $sale->created_at->format('Y-m-d H:i') }}</td>
                        <td>
                            {{ Carbon::now()->diffInDays($sale->created_at) }}
                        </td>
                        <td>{{ count($sale->stock) }}</td>
                        <td>
                            {{ $sale->amount ? $sale->amount_formatted : '' }}
                        </td>
                        <td>
                            {{ ucfirst($sale->invoice_status_alt) }}
                        </td>
                        <td>
                            @if ($sale->invoice_creation_status === 'success')
                                <a href="{{ route('sales.invoice', $sale->id) }}" target="blank">
                                    Invoice #{{ $sale->invoice_number }} @if(!is_null($sale->invoice_doc_number)){{'- '.$sale->invoice_doc_number}}@endif
                                </a>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @else
            <p class="text-info">User has no sales</p>
        @endif
    </div>
</div>
