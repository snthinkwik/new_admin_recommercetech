<div class="row  text-bold text-center mt-5">
    <div class="col-lg-6">
        <div class="row">
            <div class="col-sm-6 col-md-6 col-lg-6 ">
                <a class="text-white" href="">

                    <div class="d-flex  bg-warning-dark dashboardCard">
                        <div class="px-3 pt-4 pb-3" style="width: 50%;border-right:5px solid #ffffff ">
{{--                            <h3 class="pb-3">{{money_format(config('app.money_format'), $counting[0]['total_res']- $counting[0]['value_of_credited'])  }}</h3>--}}
                            <h3 class="pb-3">{{$counting[0]['total_res']- $counting[0]['value_of_credited']  }}</h3>
                            <p class="mb-0">Total Revenue </p>


                        </div>
                        <div class="px-3 px-3 pt-4 pb-3 " style="width: 50%">
                            <h3 class="pb-3">{{$counting[0]['number_of_items']}}</h3>
                            <p class="mb-0">Number Of Orders</p>
                        </div>
                    </div>


                </a>

            </div>

            <div class="col-sm-6 col-md-6 col-lg-6">
                <a class="text-white" href="">
                    <div class="px-3 pt-4 pb-3 bg-warning-dark mb-4 dashboardCard">
                        <h3 class="pb-3 text-white">{{$counting[0]['sold_item']}}</h3>
                        <p class="mb-0 text-white">Sold Items</p>
                    </div>
                </a>
            </div>

{{--            <div class="col-sm-6 col-md-3 col-lg-3">--}}
{{--                <a class="text-white" href="">--}}
{{--                    <div class="px-3 pt-4 pb-3 bg-primary  mb-4 dashboardCard">--}}


{{--                        <h3 class="pb-3 text-white">{{ money_format(config('app.money_format'),$counting[0]['est_net_profit'])  }}</h3>--}}
{{--                        <p class="mb-0 text-white"> EST Net Profit</p>--}}
{{--                    </div>--}}
{{--                </a>--}}
{{--            </div>--}}

        </div>

        <div class="row">

            <div class="col-sm-3 col-md-3 col-lg-3">
                <a class="text-white" href="">
                    <div class="px-3 pt-4 pb-3 bg-danger-dark mb-4 dashboardCard">
                        <h3 class="pb-3">{{$counting[0]['total_sp_non_items']}}</h3>
                        <p class="mb-0">Items Sold Non P/S</p>
                    </div>
                </a>
            </div>

{{--            <div class="col-sm-6 col-md-3 col-lg-4">--}}
{{--                <a class="text-white" href="">--}}
{{--                    <div class="px-3 pt-4 pb-3 bg-success-dark mb-4 dashboardCard">--}}
{{--                        <h3 class="pb-3 text-white">{{money_format(config('app.money_format'),$counting[0]['value_of_credited']) }}</h3>--}}
{{--                        <p class="mb-0 text-white">Value of Credit Notes</p>--}}
{{--                    </div>--}}
{{--                </a>--}}
{{--            </div>--}}

            <div class="col-sm-3 col-md-3 col-lg-3">
                <a class="text-white" href="">
                    <div class="px-3 pt-4 pb-3 bg-danger-dark mb-4 dashboardCard">
{{--                        <h3 class="pb-3">{{money_format(config('app.money_format'), $counting[0]['total_non_model'])}}</h3>--}}
                        <h3 class="pb-3">{{$counting[0]['total_non_model']}}</h3>
                        <p class="mb-0">Est Profit Non P/S</p>
                    </div>
                </a>
            </div>


            <div class="col-sm-3 col-md-3 col-lg-3">
                <a class="text-white" href="">
                    <div class="px-3 pt-4 pb-3 bg-danger-dark mb-4 dashboardCard">
                        <h3 class="pb-3">{{$counting[0]['total_sp_items']}}</h3>
                        <p class="mb-0">Items Sold P/S</p>
                    </div>
                </a>
            </div>


            <div class="col-sm-3 col-md-3 col-lg-3">
                <a class="text-white" href="">
                    <div class="px-3 pt-4 pb-3 bg-danger-dark mb-4 dashboardCard">
{{--                        <h3 class="pb-3">{{ money_format(config('app.money_format'), $counting[0]['total_sp_model']) }}</h3>--}}
                        <h3 class="pb-3">{{ $counting[0]['total_sp_model'] }}</h3>
                        <p class="mb-0">Est Profit P/S</p>
                    </div>
                </a>
            </div>


        </div>

        <div class="row">

            {{--    </div>--}}

            <div class="col-sm-3 col-md-3 col-lg-3">
                <a class="text-white" href="">
                    <div class="px-3 pt-4 pb-3 bg-success-dark mb-4 dashboardCard">
{{--                        <h3 class="pb-3 text-white">{{money_format(config('app.money_format'),$counting[0]['value_of_credited']) }}</h3>--}}
                        <h3 class="pb-3 text-white">{{$counting[0]['value_of_credited'] }}</h3>
                        <p class="mb-0 text-white">Value of Credit Notes</p>
                    </div>
                </a>
            </div>
            <div class="col-sm-3 col-md-3 col-lg-3">
                <a class="text-white" href="">
                    <div class="px-3 pt-4 pb-3 bg-success-dark mb-4 dashboardCard">
{{--                        <h3 class="pb-3 text-white">{{ money_format(config('app.money_format'), $counting[0]['profit_lost_from_customer'])}}</h3>--}}
                        <h3 class="pb-3 text-white">{{ $counting[0]['profit_lost_from_customer']}}</h3>
                        <p class="mb-0 text-white">Net Profit Lost Form Return </p>
                    </div>
                </a>
            </div>

            <div class="col-sm-3 col-md-3 col-lg-3">
                <a class="text-white" href="">
                    <div class="px-3 pt-4 pb-3 bg-primary mb-4 dashboardCard">
{{--                        <h3 class="pb-3">{{ money_format(config('app.money_format'), $counting[0]['total_est_net_profit'] - $counting[0]['profit_lost_from_customer'])}}</h3>--}}
                        <h3 class="pb-3">{{ $counting[0]['total_est_net_profit'] - $counting[0]['profit_lost_from_customer']}}</h3>
                        <p class="mb-0">Total Est Net Profit</p>
                    </div>
                </a>
            </div>

            <div class="col-sm-3 col-md-3 col-lg-3">
                <a class="text-white" href="">
                    <div class="px-3 pt-4 pb-3 bg-primary mb-4 dashboardCard">
                        @if($counting[0]['total_res']>0)
                            <?php

                            $totalEstProfitPre = ($counting[0]['total_est_net_profit'] / $counting[0]['total_res']) * 100;

                            // $totalEstProfitPre=($counting[0]['total_ex_vat']+$counting[0]['total_true_profit'])/$counting[0]['total_res']
                            ?>

                            <h3 class="pb-3 text-white">{{ number_format($totalEstProfitPre,2).'%' }}</h3>
                            {{--                <h3 class="pb-3 text-white">{{ number_format($counting[0]['total_est_profit_pre'],2).'%' }}</h3>--}}
                        @else
                            <h3 class="pb-3 text-white">{{'0%'}}</h3>
                        @endif
                        <p class="mb-0 text-white">Total Est Net Profit %</p>
                    </div>
                </a>
            </div>



        </div>
    </div>

    <div class="col-lg-6">


        @if(count($field)>0)
            <div class="btn-success p2">
                @foreach($field as $key=>$value)

                    {{$key}}:{{$value}}

                    &nbsp;&nbsp;
                @endforeach
            </div>
        @else
            <div class="btn-success p2">

                Current Day:{{Carbon\Carbon::now()->format('d M Y ')}}
            </div>
        @endif

        <table class="table table-responsive">

            <th>Day</th>
            <th>No.<br>Orders</th>
            <th>No.<br> Units</th>
            <th>Rev</th>
            <th>Est Net Profit</th>
            <th>Items Sold Non P/S</th>
            <th>Est Net Profit(Non P/S)</th>
            <th>Items Sold P/S</th>
            <th>Recomm P/S</th>


            <th>Total Est Net Profit</th>
            <th>Net Profit%</th>
            {{--                <th>True Profit </th>--}}
            {{--                <th>True Profit%</th>--}}
            {{--                <th>Est Net Profit</th>--}}
            {{--                <th>Est Net Profit%</th>--}}


            <tbody>

            @foreach ($stockList as $data)

                <tr style="text-align:center;font-weight: normal">
                    <td>{{ \Carbon\Carbon::parse($data['day'])->format('d M Y') }}</td>
                    <td>{{$data['count']}}</td>
                    <td>{{$data['number_of_sold']}} </td>

                    <td @if($data['rev']<0) class="text-danger" @endif>
{{--                        {{ money_format(config('app.money_format'),$data['rev'])}}--}}
                        {{ $data['rev']}}
                    </td>
                    <td>
{{--                        {{ money_format(config('app.money_format'),$data['est_net_profit'])}}--}}
                        {{ $data['est_net_profit']}}
                    </td>
                    <td>{{$data['total_items_sold_non_ps']}}</td>
                    <td>
{{--                        {{money_format(config('app.money_format'),$data['est_profit_sp_non_model'])}}--}}

                        {{$data['est_profit_sp_non_model']}}
                    </td>
                    <td>{{$data['total_items_sold_ps']}}</td>
                    <td>
{{--                        {{money_format(config('app.money_format'),$data['est_profit_sp_model'])}} --}}

                        {{$data['est_profit_sp_model']}}
                    </td>
                    @if(abs($data['est_profit_sp_non_model'])>0 && abs($data['est_profit_sp_model']) > 0 )
                        <td style="background: #808080">
{{--                            {{ money_format(config('app.money_format'),$data['est_profit_sp_model']+ $data['est_profit_sp_non_model'])}}--}}

                            {{ $data['est_profit_sp_model']+ $data['est_profit_sp_non_model']}}
                        </td>
                    @else

                        <td style="background: #808080">
{{--                            {{ money_format(config('app.money_format'),$data['total_est_net_profit']) }}--}}
                            {{ $data['total_est_net_profit'] }}
                        </td>
                    @endif

                    <?php
                    if (abs($data['est_profit_sp_non_model']) > 0 && abs($data['est_profit_sp_model']) > 0) {
                        $totalPs = $data['est_profit_sp_model'] + $data['est_profit_sp_non_model'];

                    } else {
                        $totalPs = $data['total_est_net_profit'];
                    }

                    $totalEstNetPre = ($totalPs / $data['rev']) * 100
                    ?>
                    <td>{{number_format($totalEstNetPre,2)."%"  }}</td>
                    {{--                    <td @if($data['true_profit']<0) class="text-danger" @endif>{{money_format(config('app.money_format'),$data['true_profit'])  }}</td>--}}
                    {{--                    <td @if($data['true_profit_pe']<0) class="text-danger" @endif >{{$data['true_profit_pe']}}</td>--}}
                    {{--                    <td @if($data['est_net_profit']<0) class="text-danger" @endif>{{money_format(config('app.money_format'),$data['est_net_profit'])}}</td>--}}
                    {{--                    <td @if($data['est_net_profit_pe']<0) class="text-danger" @endif>{{$data['est_net_profit_pe']}}</td>--}}

                </tr>

            @endforeach
            </tbody>
        </table>

        {{--<div id="stock-pagination-wrapper">--}}
        {{--{!! $salesQuery->render() !!}--}}
        {{--</div>--}}
    </div>
</div>
