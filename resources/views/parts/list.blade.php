<table class="table table-striped table-condensed">
    <thead>
        <tr id="item-sort">
            <th name="id">No.</th>
            <th name="name">Name</th>
            <!-- <th name="colour">Colour</th> -->
            <th name="type">Type</th>
            <!-- <th name="quantity_inbound">Inbound Qty</th> -->
            <th name="quantity">RCT Qty</th>
            <th name="cost">Cost</th>
            <th name="supplier_id">Supplier</th>
            <!-- <th>Edit</th> -->
        </tr>
    </thead>
    <tbody>

        @foreach($parts as $part)
        <tr>
            <td>{{ $part->id }}</td>
            <td><a href="{{ route('parts.single', ['id' => $part->id]) }}">{{ $part->name }}</a></td>
            <!-- <td>{{ $part->colour }}</td> -->
            <td>{{ $part->type }}</td>
            <!-- <td>{{ $part->quantity_inbound }}</td> -->
            <td>{{ $part->quantity }}</td>
            <td>{{ $part->cost_formatted }}</td>
            <td>@if(isset($part->suppliers))
                {{ $part->suppliers->name }}
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>