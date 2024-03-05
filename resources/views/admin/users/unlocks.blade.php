<a class="btn btn-default btn-block" data-toggle="collapse" data-target="#unlocks_table">Unlock Orders <span class="badge">{{ count($orders) }}</span></a>
<div class="panel panel-default collapse" id="unlocks_table">
    <div class="panel-body">
        @if (count($orders))
            <table class="table table-striped table-condensed">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Status</th>
                    <th>Network</th>
                    <th>Models</th>
                    <th>IMEIs</th>
                    <th>Amount</th>
                    <th>Invoice</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($orders as $order)
                    <tr>
                        <td>{{ $order->id }}</td>
                        <td>{{ $order->status }}</td>
                        <td>{{ $order->network }}</td>
                        <td>{{ $order->models }}</td>
                        <td>{{ implode(', ', $order->imeis) }}</td>
                        <td>{{ $order->amount_formatted }}</td>
                        <td>
                            @if($order->invoice_number)
                                <a href="{{ route('unlocks.invoice', $order->id) }}" class="btn btn-block btn-xs btn-default" target="blank">
                                    Invoice #{{ $order->invoice_number }}
                                </a>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @else
            <p class="text-info">User has no unlock orders</p>
        @endif
    </div>
</div>
