<?php

namespace App\Http\Controllers;

use App\Models\PhoneCheck;
use App\Models\PhoneCheckReports;
use Illuminate\Http\Request;

class PhoneCheckReportController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function reports($id)
    {

        $phoneCheckReport=PhoneCheckReports::find($id);
        $curl = curl_init();
        $phoneCheck=PhoneCheck::where("stock_id",$phoneCheckReport->stock_id)->first();
        $data=[
            'report_id'=>$phoneCheckReport->report_id,
            'username'=>$phoneCheck->station_id,
            'apikey'=>config('services.phonecheck.new_api_key')
        ];

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://clientapiv2.phonecheck.com/cloud/cloudDB/A4Report",

            // CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        return  view('PhoneCheckReport.index',compact('response'));
    }

    public function eraserReports($id)
    {

        $phoneCheckReport=PhoneCheckReports::find($id);
        $report=$phoneCheckReport->eraser_report;
        return  view('PhoneCheckReport.eraserReport',compact('report'));
    }

}
