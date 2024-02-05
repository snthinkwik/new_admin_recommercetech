<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;
use Illuminate\Support\Facades\Session;


class NonSerialisedStockRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        Session::put("non",'1');
        return [
            'product_id'=>'required',
            'vat_type'=>'required',
            'sale_price'=>'required',
            'grade'=>'required'
        ];
    }
}
