<?php

namespace App\Http\Controllers;

use App\Models\PhoneCheck;
use App\Models\Stock;
use Illuminate\Http\Request;

class PhoneCheckController extends Controller
{
    public function postApiImei(Request $request)
    {
        $status = "error";
        $code = 400;
        $message = "Error";
        $rctref = "";

        $imei = $request->imei;

        if(!$request->header('key') || $request->header('key') != config('services.phonecheck.open_api_key')) {
            $code = 403;
            $message = "Key missing or Invalid";
        } elseif(!$imei || strlen($imei) != 15) {
            $code = 400;
            $message = "IMEI missing or Invalid";
        } else {
            $stock = Stock::where('imei', $imei)->first();
            if($stock) {
                $status = "success";
                $message = "success";
                $code = 200;
                $rctref = $stock->our_ref;
            } else {
                $message = "Item not found";
            }
        }

        $response = ['status' => $status, "message" => $message, "rctref" => $rctref];
        //alert("PhoneCheck API IMEI Test |\n".json_encode($request->all())." |\n".$request->header('key')." |\nResponse: ".json_encode($response)." |\n$code", ['radoslaw.kowalczyk@netblink.net']);
        return response()->json($response, $code);
    }

    public function getData(){
        $phonecheck=PhoneCheck::paginate(config('app.pagination'));

        $phoneCount=PhoneCheck::where('status','New')->count();


        return view('PhoneCheckReport.list',compact('phonecheck','phoneCount'));

    }
}
