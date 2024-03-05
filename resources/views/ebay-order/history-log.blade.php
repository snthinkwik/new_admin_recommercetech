<?php

use Carbon\Carbon;
?>
@extends('app')

@section('title', 'Channel Grabber History Log')

@section('content')

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div id="universal-table-wrapper">
                <table class="table table-bordered table-hover">
                    <tbody>
                        <tr>
                            <th>Log</th>
                            <th>Date</th>
                        </tr>
                        @foreach($historyLog as $log)
                        <?php
                        $startTime = Carbon::parse($log->script_started);

                        $finishTime = Carbon::parse($log->script_finished);
                        $s = $finishTime->diffInSeconds($startTime);

                        $mins = floor($s / 60 % 60);
                        $secs = floor($s % 60);

                        $start = "Channel Grabber has started to import at " . date('d-m-Y G:i:s a ', strtotime($log->script_started));
                        $end = "Channel Grabber completed the import at " . date('d-m-Y G:i:s a', strtotime($log->script_finished));
                        $duration = "Channel Grabber took ";

                        if ($mins > 0) {
                            if ($mins == 1)
                                $duration .= $mins . " minute and ";
                            else
                                $duration .= $mins . " minutes and ";
                        }

                        if ($secs > 1)
                            $duration .= $secs . " seconds to ";
                        else
                            $duration .= $secs . " second to ";

                        if ($log->import_count == 1)
                            $duration .="import " . $log->import_count . " new order";
                        else
                            $duration .="import " . $log->import_count . " new orders";
                        ?>
                        <tr>
                            <td>
                                @if($log->import_count > 0)
                                {{$start}}  <br>
                                {{$duration}}  <br>
                                {{$end}}
                                @else
                                No new records have been found
                                @endif
                            </td>
                            <td>
                                {{ $log->created_at->format("d M Y H:i:s") }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div id="universal-pagination-wrapper">
                {!! $historyLog->appends(Request::All())->render() !!}
            </div>
        </div>
    </div>

</div>
@endsection