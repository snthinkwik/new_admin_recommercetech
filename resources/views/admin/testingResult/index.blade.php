@extends('app')

@section('title', 'Testing Result')

@section('content')
    <div class="container">

        <h2>Testing Results Last 7 Days </h2>

        @include('messages')



        <?php

//            $mondayCount=0;
//            $mondayErasure=0;
//            $mondayPending=0;
//            $mondayDate='';
//            $tuesdayCount=0;
//            $tuesdayErasure=0;
//            $tuesdayPending=0;
//            $tuesdayDate='';
//            $wednesdayCount=0;
//            $wednesdayErasure=0;
//            $wednesdayPending=0;
//            $wednesdayDate='';
//            $thursdayCount=0;
//            $thursdayErasure=0;
//            $thursdayPending=0;
//            $thursdayDate='';
//            $fridayCount=0;
//            $fridayErasure=0;
//            $fridayPending=0;
//            $fridayDate='';
//            $saturdayCount=0;
//            $saturdayErasure=0;
//            $saturdayPending=0;
//            $saturdayDate='';
//            $sundayCount=0;
//            $sundayErasure=0;
//            $sundayPending=0;
//            $sundayDate='';


//        foreach ($testResultComplete as $result){
//
//                foreach ($result as $key=>$item){
//
//                    if($key==="Monday"){
//                        $mondayCount+=$item['test_count'];
//                        $mondayErasure+=$item['erasure'];
//                        $mondayDate=\Carbon\Carbon::parse($item['date'])->format('d/m/Y') ;
//                        $mondayPending+=$item['pending_count'];
//                    }
//                    if($key==="Tuesday"){
//                        $tuesdayCount+=$item['test_count'];
//                        $tuesdayErasure+=$item['erasure'];
//                        $tuesdayDate= \Carbon\Carbon::parse($item['date'])->format('d/m/Y');
//                        $tuesdayPending+=$item['pending_count'];
//                    }
//                    if($key==="Wednesday"){
//
//                        $wednesdayCount+=$item['test_count'];
//                        $wednesdayErasure+=$item['erasure'];
//                        $wednesdayDate=\Carbon\Carbon::parse($item['date'])->format('d/m/Y');
//                        $wednesdayPending+=$item['pending_count'];
//                    }
//                    if($key==="Thursday"){
//                        $thursdayCount+=$item['test_count'];
//                        $thursdayErasure+=$item['erasure'];
//                        $thursdayDate=\Carbon\Carbon::parse($item['date'])->format('d/m/Y');
//                        $thursdayPending+=$item['pending_count'];
//                    }
//
//                    if($key==="Friday"){
//                        $fridayCount+=$item['test_count'];
//                        $fridayErasure+=$item['erasure'];
//                        $fridayDate=\Carbon\Carbon::parse($item['date'])->format('d/m/Y');
//                        $fridayPending+=$item['pending_count'];
//                    }
//
//                    if($key==="Saturday"){
//                        $saturdayCount+=$item['test_count'];
//                        $saturdayErasure+=$item['erasure'];
//                        $saturdayDate=\Carbon\Carbon::parse($item['date'])->format('d/m/Y');
//                        $saturdayPending+=$item['pending_count'];
//                    }
//                    if($key==="Sunday"){
//                        $sundayCount+=$item['test_count'];
//                        $sundayErasure+=$item['erasure'];
//                        $sundayDate=\Carbon\Carbon::parse($item['date'])->format('d/m/Y');
//                        $sundayPending+=$item['pending_count'];
//                    }
//                }
//            }

        ?>

        <div class="row">
            <div class="col-md-6">
{{--                <table class="table table-hover table-bordered">--}}
{{--                    <tr>--}}
{{--                        <td></td>--}}
{{--                        <td>Monday--}}
{{--                            {{$mondayDate}}--}}
{{--                        </td>--}}
{{--                        <td>Tuesday--}}
{{--                        {{$tuesdayDate}}--}}
{{--                        </td>--}}
{{--                        <td>Wednesday--}}
{{--                            {{$wednesdayDate}}--}}
{{--                        </td>--}}
{{--                        <td>Thursday--}}
{{--                            {{$thursdayDate}}--}}
{{--                        </td>--}}
{{--                        <td>Friday--}}
{{--                            {{$fridayDate}}--}}
{{--                        </td>--}}
{{--                        <td>Saturday--}}
{{--                            {{$saturdayDate}}--}}
{{--                        </td>--}}
{{--                        <td>Sunday--}}
{{--                            {{$sundayDate}}--}}
{{--                        </td>--}}
{{--                    </tr>--}}

{{--                    <tr>--}}
{{--                        <th>No of Tests Completed</th>--}}
{{--                        <td><a href="{{route('stock')."?test_status=Complete&term=&grade=&status="}}">--}}

{{--                                {{$mondayCount}}--}}

{{--                            </a>--}}
{{--                        </td>--}}
{{--                        <td><a href="{{route('stock')."?test_status=Complete&term=&grade=&status="}}">{{$tuesdayCount}}</a></td>--}}
{{--                        <td><a href="{{route('stock')."?test_status=Complete&term=&grade=&status="}}">{{$wednesdayCount}}</a></td>--}}
{{--                        <td><a href="{{route('stock')."?test_status=Complete&term=&grade=&status="}}">{{$thursdayCount}}</a></td>--}}
{{--                        <td><a href="{{route('stock')."?test_status=Complete&term=&grade=&status="}}">{{$fridayCount}}</a></td>--}}
{{--                        <td><a href="{{route('stock')."?test_status=Complete&term=&grade=&status="}}">{{$saturdayCount}}</a></td>--}}
{{--                        <td><a href="{{route('stock')."?test_status=Complete&term=&grade=&status="}}">{{$sundayCount}}</a></td>--}}

{{--                    </tr>--}}
{{--                    <tr>--}}
{{--                        <th>No. of Test Pending for in stock</th>--}}
{{--                        <td>{{$mondayPending}}</td>--}}
{{--                        <td>{{$tuesdayPending}}</td>--}}
{{--                        <td>--}}


{{--                            {{$wednesdayPending}}</td>--}}
{{--                        <td>{{$thursdayPending}}</td>--}}
{{--                        <td>{{$fridayPending}}</td>--}}
{{--                        <td>{{$saturdayPending}}</td>--}}
{{--                        <td>{{$sundayPending}}</td>--}}

{{--                    </tr>--}}

{{--                    <tr>--}}
{{--                        <th>No. of Erasures Tests Completed</th>--}}
{{--                        <td>{{$mondayErasure}}</td>--}}
{{--                        <td>{{$tuesdayErasure}}</td>--}}
{{--                        <td>{{$wednesdayErasure}}</td>--}}
{{--                        <td>{{$thursdayErasure}}</td>--}}
{{--                        <td>{{$fridayErasure}}</td>--}}
{{--                        <td>{{$saturdayErasure}}</td>--}}
{{--                        <td>{{$sundayErasure}}</td>--}}

{{--                    </tr>--}}





{{--                </table>--}}





                    <table class="table table-hover table-bordered">
                        <tr>
                            <td></td>

                            @foreach($testResultComplete as $ty)
                                @foreach($ty as $key=>$value)
                                <td>
                                {{$key}}
                                    <br>
                                {{ \Carbon\Carbon::parse( $value['date'])->format('d/m/Y')}}
                                </td>
                                @endforeach
                            @endforeach

                        </tr>
                        <tr>
                            <th>No of Tests Completed</th>
                            @foreach($testResultComplete as $ty)

                                @foreach($ty as $key=>$value)

                                    <td><a href="{{route('stock')."?test_status=Complete&term=&grade=&status="}}">{{$value['test_count']}}</a></td>
                                @endforeach
                            @endforeach
                        </tr>

                        <tr>
                            <th>No. of Test Pending for in stock</th>
                            @foreach($testResultComplete as $ty)

                                @foreach($ty as $key=>$value)

                                    <td>{{$value['pending_count']}}</td>
                                @endforeach
                            @endforeach
                        </tr>
                        <tr>
                            <th>No. of Erasures Tests Completed</th>

                            @foreach($testResultComplete as $ty)

                                @foreach($ty as $key=>$value)

                                    <td>{{$value['erasure']}}</td>
                                @endforeach
                            @endforeach

                        </tr>



                    </table>

            </div>
    </div>

@endsection

