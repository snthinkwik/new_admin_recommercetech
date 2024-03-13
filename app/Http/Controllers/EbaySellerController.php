<?php

namespace App\Http\Controllers;

use App\Models\EBaySeller;
use Illuminate\Http\Request;

class EbaySellerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $seller=EBaySeller::all();
        return view('ebay-seller.index',compact('seller'));
    }

    public function create(){
        return view('ebay-seller.create');
    }

    public function postSave(Request $request)
    {

        if(!is_null($request->id)){

            $this->validate($request, [
                'name'=>'required',
                'user_name' => 'required|unique:ebay_sellers,user_name,'.$request->id
            ]);


        }else{
            $this->validate($request, [
                'user_name' => 'required|unique:ebay_sellers',
                'name' => 'required'
            ]);
        }

        if(!is_null($request->id) || isset($request->id) ){
            $seller=EBaySeller::find($request->id);

        }else{
            $seller=new EBaySeller();
        }
        $seller->name=$request->name;
        $seller->user_name=$request->user_name;
        $seller->save();

        return back()->with('messages.success',"Seller Added Successfully");

    }

    public function update($id){
        $seller=null;
        if($id){
            $seller=EBaySeller::find($id);
        }

        return view('ebay-seller.create',compact('seller'));
    }

    public  function  delete($id){
        $seller=EBaySeller::find($id);
        if(!is_null($seller)){
            $seller->delete();
        }
        return back()->with('messages.success',"Seller Deleted Successfully");
    }


}
