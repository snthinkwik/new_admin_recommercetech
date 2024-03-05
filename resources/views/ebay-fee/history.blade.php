
@extends('app')

@section('title', 'Channel Grabber Ebay Fees History Log')

@section('content')

<div class="container">
    <div class="flexbox-md">
        <div class="mb-4">
            <a class="btn btn-default" href="{{ session('ebay-fee.index') ?: route('ebay-fee.index') }}">Back to list</a>
        </div>        
    </div>
    <div class="row">
        <div class="col-md-12">
            <div id="universal-table-wrapper">
                <table class="table table-bordered table-hover">
                    <tbody>
                        <tr>
                            <th>Log</th>
                            <th>Date</th>
                        </tr>
                        @foreach($ebayFeesHistory as $history)
                        <tr>
                            <td>
                                {{$history->content}}
                            </td>
                            <td>
                                {{ $history->created_at->format("d M Y H:i:s") }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div id="universal-pagination-wrapper">
                {!! $ebayFeesHistory->appends(Request::All())->render() !!}
            </div>
        </div>
    </div>

</div>
@endsection