
<table class="table table-bordered table-hover">
    <tr>

        <th>Name</th>
        <th>User Name</th>
        <th></th>
    </tr>
    @foreach($seller as $ct)
        <tr>

            <td>{{ $ct->name }}</td>
            <td>{{$ct->user_name}}</td>
            <td><a href="{{route('ebay-seller.update',['id'=>$ct->id])}}"> <i class="fa fa-pencil-square-o" aria-hidden="true"></i></a>|
                <a href="{{route('ebay-seller.delete',['id'=>$ct->id])}}"> <i class="fa fa-remove text-danger" aria-hidden="true"></i></a>
            </td>
        </tr>
    @endforeach
</table>
