
    <table class="table table-bordered table-hover">
        <tr>

            <th>Name</th>
            <th>eBay Category Id</th>
            <th>validation</th>
            <th>Update</th>
            <th>Delete</th>
        </tr>
        @foreach($category as $ct)
            <tr>

                <td>{{ $ct->name }}</td>
                <td>{{$ct->eBay_category_id}}</td>
                <td>{{$ct->validation .'%'}}</td>
                <td><a href="{{route('category.update',['id'=>$ct->id])}}"> <i class="fa fa-pencil-square-o" aria-hidden="true"></i></a></td>
                <td><a href="{{route('category.delete',['id'=>$ct->id])}}"><i class="fa fa-remove text-danger" aria-hidden="true"></i></a></td>
            </tr>
        @endforeach
    </table>
