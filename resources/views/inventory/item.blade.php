

{{--<form>--}}

<table class="table small inventory table-bordered">
    <thead>
    <tr id="item-sort">
        <th name="sku">SKU</th>
        <th name="type">Type</th>
        <th name="make">Make</th>
        <th name="model">Model</th>
        <th name="capacity">Capacity</th>
        <th name="colour">Colour</th>
        <th name="quantity_in_stock">In Stock</th>
        <th></th>
    </tr>
    </thead>
    <tbody>
    @foreach ($inventory as $item)
        <tr>
            <td width="105">
                {{$item->sku}}</td>
            <td>{{$item->type}}</td>
            <td>{{$item->make}}</td>
            <td>{{$item->model}}</td>
            <td width="90">{{$item->capacity}}</td>
            <td width="100">{{$item->colour}}</td>
            <td width="80">{{$item->quantity_in_stock}}</td>

            <td align="center">
                <a href="{{route('inventory.single',['id'=>$item->id])}}"><i class="fa fa-edit btn btn-success" ></i> </a>      <a href="{{route('inventory.delete',['id'=>$item->id])}}" onclick="return confirm('Are you sure you want to delete this inventory?')" title="Delete Inventory" tabIndex="-1"> <i class="fa fa-trash btn btn-danger" ></i></a>
            </td>
        </tr>

    @endforeach
    </tbody>
</table>
{{--</form>--}}
