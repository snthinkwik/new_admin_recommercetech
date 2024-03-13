<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TestingResultController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {

        $date = \Carbon\Carbon::today()->subDays(7);


        $testStatusComplete=Stock::where('phone_check_create_at','>=',$date)

            ->groupBy(DB::raw('Date(phone_check_create_at)'))
            ->orderBy(DB::raw('Date(phone_check_create_at)'),'DESC')
            ->get();




        $testResultComplete=[];


        foreach ($testStatusComplete as $test){
            $erasureStatus=0;

            $createdAt=Carbon::parse($test->phone_check_create_at)->format('Y-m-d');


            $stockComplete=Stock::where('test_status',Stock::TEST_STATUS_COMPLETE)->where(DB::raw('CAST(phone_check_create_at as date)'), '=', $createdAt)->get();
            $stockPending=Stock::whereNull('test_status')->where('test_status',Stock::TEST_STATUS_PENDING)->where('test_status',Stock::TEST_STATUS_UNTESTED)->where(DB::raw('CAST(phone_check_create_at as date)'), '=', $createdAt)
                ->whereNotIn('status',[Stock::STATUS_SOLD,Stock::STATUS_PAID,Stock::STATUS_DELETED,Stock::STATUS_RETURNED_TO_SUPPLIER,Stock::STATUS_DELETED,Stock::STATUS_LOST])
                ->get();



            $day = Carbon::parse($test->phone_check_create_at)->format('l');



            foreach ($stockComplete as $test){

                if(!is_null($test->phone_check)){
                    if(strpos($test->phone_check->report_render, 'Erasure Status') !== false){
                        $whatIWant = substr($test->phone_check->report_render, strpos($test->phone_check->report_render, "Erasure Status") + 15);
                        $output = substr($whatIWant, 0, strpos($whatIWant, 'Working'));
                        if(strip_tags($output)===" Yes"){
                            $erasureStatus++;
                        }


                    }

                }
            }

            $testResultComplete[
            ]=[
                $day=>[
                    'test_count'=>count($stockComplete),
                    'erasure'=>$erasureStatus,
                    'pending_count'=>count($stockPending),
                    'date'=>$test->phone_check_create_at,
                ]
            ];


        }


        return view('admin.testingResult.index',compact('testResultComplete'));

    }
}
