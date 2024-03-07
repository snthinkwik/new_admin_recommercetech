
<table class="table table-bordered table-hover">
    <tr>

        <th>Name</th>
        <th>Code</th>

    </tr>
    @foreach($colour as $ct)
        <tr>

            <td>{{ $ct->pr_colour }}</td>
            <td>{{$ct->code}}</td>
            <td><a href="{{route('colour.update',['id'=>$ct->id])}}"> <i class="fa fa-pencil-square-o" aria-hidden="true"></i></a>
            </td>
        </tr>
    @endforeach
</table>
