<a class="btn btn-default btn-block" data-toggle="collapse" data-target="#user-returns">Returns <span class="badge">{{ $user->stock_returns()->count() }}</span></a>
<div class="panel panel-default collapse" id="user-returns">
    <div class="panel-body">
        @if ($user->stock_returns()->count())
            <table class="table table-striped table-condensed">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>RMA Expiration Date</th>
                    <th>Date Created</th>
                    <th>No. Items</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($user->stock_returns()->get() as $return)

                    <tr>
                        <td>
                            {{$return->id}}
                            {{--@if($return->id)--}}
                            {{--<a href="{{ route('returns.single', ['id' => $return->id]) }}">{{ $return->id }}</a>--}}
                            {{--@else ---}}
                            {{--@endif--}}
                        </td>
                        <td>{{ $return->valid_to_date }}</td>
                        <td>{{ $return->created_at }}</td>
                        <td>{{ $return->stock_return_items()->count() }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @else
            <div class="alert alert-info">User has no returns</div>
        @endif
    </div>
</div>
