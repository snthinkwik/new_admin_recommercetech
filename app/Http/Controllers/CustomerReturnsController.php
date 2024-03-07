<?php

namespace App\Http\Controllers;

use App\Models\CustomerReturns;
use Illuminate\Http\Request;

class CustomerReturnsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function getIndex()
    {
        $customerReturn=CustomerReturns::paginate(config('app.pagination'));

        return view('customer-return.index', compact('customerReturn'));
    }

    public function getCustomerReturn($id){

        $customerReturn=CustomerReturns::find($id);
        return view('customer-return.create', compact('customerReturn'));
    }

    public function create(){

        return view('customer-return.create');

    }

    public function postSave(Request $request){


//        $this->validate($request, [
//            'platform' => 'required|unique:seller_fees,platform,'.$request->id,
//        ]);



        $customerReturn= CustomerReturns::firstOrCreate([
            'id'=>$request->id
        ]);

        $customerReturn->items_credited=$request->items_credited;
        $customerReturn->value_of_credited=$request->value_of_credited;
        $customerReturn->profile_lost=$request->profit_lost;

        $customerReturn->save();

        return back()->with('messages.success',"data added successfully");

    }
}
