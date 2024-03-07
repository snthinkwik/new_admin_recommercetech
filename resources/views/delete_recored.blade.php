<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Email</title>
</head>
<body style="font-family: sans-serif">
<table border="2">
    <th>SaleId</th>
    <th>User Id</th>
    <th>InvoiceNumber</th>
    <th>StockItems</th>
    <th>Create At</th>
    <th>Updated At</th>
    <th>Deleted At</th>

    @foreach($delete as $de)
    <tr>
        <td>{{$de->id}}</td>
        <td>{{$de->user_id}}</td>
        <td>{{$de->invoice_number}}</td>
        <td>
            @if(!is_null($de->newSalesStock()))
                @foreach($de->newSalesStock()->get() as $stock)
                    {{$stock->id}}<br>
                @endforeach
            @endif
        </td>
        <td>
            {{$de->created_at}}
        </td>
        <td>
            {{$de->updated_at}}
        </td>
        <td>
            {{$de->deleted_at}}
        </td>

    </tr>
    @endforeach
</table>
</body>