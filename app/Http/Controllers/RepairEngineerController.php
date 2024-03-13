<?php

namespace App\Http\Controllers;

use App\Models\RepairEngineer;
use Illuminate\Http\Request;

class RepairEngineerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function getIndex()
    {

        $engineer=RepairEngineer::paginate(config('app.pagination'));



        return view('repair-engineer.index',compact('engineer'));

    }


    public function postSave(Request $request){


        $this->validate($request, [
            'name' => 'required',

        ]);

        $engineer= RepairEngineer::firstOrNew([
            'id' => $request->id
        ]);

        $engineer->name=$request->name;
        $engineer->company=$request->company;
        $engineer->save();

        return back()->with("messages.success","Engineer SuccessFully Added");

    }


    public function getEngineer(Request $request){

        $engineer=RepairEngineer::find($request->id);

        return['data'=>$engineer];



    }


}
