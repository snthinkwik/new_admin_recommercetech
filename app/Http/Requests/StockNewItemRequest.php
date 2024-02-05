<?php namespace App\Http\Requests;
use App\Models\Stock;

class StockNewItemRequest extends Request {

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
     * @return array
     */
    public function rules()
    {

        if(is_null($this->imei)){
            $rules = [
                'grade' => 'required|in:' . implode(',', Stock::getAvailableGrades()),
                'name' => 'required',
                'capacity' => 'required',
                'lcd_status' => 'required|in:'.implode(',', Stock::getAvailableLcdStatuses()),
                'condition' => 'required|in:'.implode(',', Stock::getAvailableConditions()),
                // Double unique rules because imei and serial number have to be unique even relative to each other.
                'serial' => 'nullable|required|unique:new_stock|unique:new_stock,imei',
                'imei' => 'nullable|required|unique:new_stock|unique:new_stock,serial',
                'third_party_ref' => 'unique:new_stock',
                'code'=>'required|in:784199',
                'product_type'=>'required',
                'purchase_price'=>'required|numeric',
                'sale_price'=>'required|numeric',
                'vat_type'=>'required',
                'ps_model'=>'required',
                'supplier_name'=>'required'
            ];
        }else{
            $rules = [
                'grade' => 'required|in:' . implode(',', Stock::getAvailableGrades()),
                'name' => 'required',
                'capacity' => 'required',
                'lcd_status' => 'required|in:'.implode(',', Stock::getAvailableLcdStatuses()),
                'condition' => 'required|in:'.implode(',', Stock::getAvailableConditions()),
                // Double unique rules because imei and serial number have to be unique even relative to each other.
                'serial' => 'nullable|required|unique:new_stock|unique:new_stock,imei',
                'imei' => 'required|imei|unique:new_stock|unique:new_stock,serial',
                'third_party_ref' => 'unique:new_stock',
                'code'=>'required|in:784199',
                'product_type'=>'required',
                'purchase_price'=>'required|numeric',
                'sale_price'=>'required|numeric',
                'vat_type'=>'required',
                'ps_model'=>'required',
                'supplier_name'=>'required'
            ];

        }


        if ($this->serial) {
            $rules['imei'] = str_replace('required|', '', $rules['imei']);
        }
        elseif ($this->imei) {
            $rules['serial'] = str_replace('required|', '', $rules['serial']);
        }

        return $rules;
    }

    /**
     * We need to override the connection for the 'unique' rule in rules().
     */
    protected function getValidatorInstance()
    {

        $validator = parent::getValidatorInstance();
        $verifier = app('validation.presence');
        $validator->setPresenceVerifier($verifier);
        return $validator;
    }

    public function messages()
    {
        return [
            'serial.required' => 'Either serial number or IMEI is required',
            'imei.required' => 'Either serial number or IMEI is required',
            'imei.unique' => 'This IMEI is already present in the database.',
            'serial.unique' => 'This serial number is already present in the database.',
            'third_party_ref.unique' => 'This 3rd-party ref is already present in the database.',
            'code.in'=> 'Invalid Authorisation Code',
            'product_type.required' => 'The category field is required.',

        ];
    }
}
