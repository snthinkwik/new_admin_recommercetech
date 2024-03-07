<?php

namespace App\Http\Controllers;

use App\Models\SellerFees;
use Illuminate\Http\Request;

class SellerFeesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function getIndex()
    {

        $sellerFees=SellerFees::paginate(config('app.pagination'));

        return view('seller-fees.index',compact('sellerFees'));

    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function getCreate(){
        return view('seller-fees.create');
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function getSellerFees($id)
    {

        $sellerFees=SellerFees::find($id);

        return view('seller-fees.create',compact('sellerFees'));
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function postSave(Request $request)
    {

        $this->validate($request, [
            'platform' => 'required|unique:seller_fees,platform,'.$request->id,
        ]);

        $sellerFees= SellerFees::firstOrCreate([
            'id'=>$request->id
        ]);

        $sellerFees->platform=$request->platform;
        $sellerFees->platform_fees=$request->platform_fees;
        $sellerFees->uk_shipping_cost_under_20=$request->uk_shipping_cost_under_20;
        $sellerFees->uk_shipping_cost_above_20=$request->uk_shipping_cost_above_20;
        $sellerFees->uk_non_shipping_cost_under_20=$request->uk_non_shipping_cost_under_20;
        $sellerFees->uk_non_shipping_above_under_20=$request->uk_non_shipping_above_under_20;
        $sellerFees->accessories_cost_ex_vat=$request->accessories_cost_ex_vat;
        $sellerFees->warranty_accrual=$request->warranty_accrual;

        $sellerFees->save();

        return back()->with('messages.success', "SuccessFully  Added Seller Fees");

    }



}
