<?php

namespace App\Http\Controllers;

use App\Models\Colour;
use Illuminate\Http\Request;

class ColourController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $colour=Colour::paginate(config('app.pagination'));
        return  view('colour.index',compact('colour'));



    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {

        return  view('colour.create');

    }


    public function postSave(Request $request){



        if(!is_null($request->id)){

            $this->validate($request, [
                'pr_colour' => 'required|unique:colour,pr_colour,'.$request->id
            ]);


        }else{
            $this->validate($request, [
                'pr_colour' => 'required|unique:colour'
            ]);
        }

        if(!is_null($request->id) || isset($request->id) ){
            $colour=Colour::find($request->id);

        }else{
            $colour=new Colour();
        }


        $colour->pr_colour=$request->pr_colour;
        $colour->code=$request->code;
        $colour->save();

        return back()->with('messages.success',"Colour Added Successfully");

    }


    public function update($id){


        $colour=null;
        if($id){
            $colour=Colour::find($id);
        }





        return view('colour.create',compact('colour'));
    }


}
