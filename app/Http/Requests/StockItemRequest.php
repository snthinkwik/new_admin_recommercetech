<?php

namespace App\Http\Requests;

use App\Models\Stock;
use Illuminate\Support\Facades\Auth;

class StockItemRequest extends Request
{
    protected $validateLcd = false;
    protected $salesPriceRequired = false;
    protected $productTypeRequired = false;

    public function setValidateLcd($validate)
    {
        $this->validateLcd = $validate;
    }

    public function setSalesPriceRequired($required)
    {
        $this->salesPriceRequired = $required;
    }

    public function setProductTypeRequired($required)
    {
        $this->productTypeRequired = $required;
    }

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $item = $this->id ? Stock::findOrFail($this->id) : null;


        if(isset($item->non_serialised)){

            return [
                // 'product_id'=>'required',
                'vat_type'=>'required',
                'sale_price'=>'required',
                'grade'=>'required'
            ];

        }else{

            $rules = [
                'colour' => 'required',
                'grade' => 'required|in:' . implode(',', Stock::getAvailableGrades('all')),
                // Double unique rules because imei and serial number have to be unique even relative to each other.
                'serial' => 'required|unique:new_stock|unique:new_stock,imei',
                'imei' => 'required|imei|unique:new_stock|unique:new_stock,serial',
                'purchase_date' => ['required', 'regex:#^(\d\d\d\d-\d\d-\d\d|\d\d/\d\d/\d\d(\d\d)?)( \d\d:\d\d(:\d\d)?)?$#'],
                //'purchase_price' => 'required',
                'third_party_ref' => 'unique:new_stock',
            ];

            if ($this->serial) {
                $rules['imei'] = str_replace('required|', '', $rules['imei']);
            }
            elseif ($this->imei) {
                $rules['serial'] = str_replace('required|', '', $rules['serial']);
            }

            // Prevent "already taken" error when editing item and leaving unique fields as they are.
            if ($item && $item->third_party_ref == $this->third_party_ref) {
                unset($rules['third_party_ref']);
            }
            if ($item && $item->serial && $item->serial == $this->serial) {
                unset($rules['serial']);
            }
            if ($item && $item->imei && $item->imei == $this->imei) {
                unset($rules['imei']);
            }

            if ($this->validateLcd) {
                $statuses = Stock::getAvailableLcdStatuses(false);
                $rules['lcd_status'] = ['required', 'in:' . implode(',', $statuses)];
            }

            if ($this->salesPriceRequired) {
                $rules['sale_price'] = 'required';
            }

            if ($this->productTypeRequired) {
                $rules['product_type'] = 'required|in:'.implode(',', Stock::getAvailableProductTypes());
            }

            if($item && $item->phone_check_passed) {
                unset($rules['colour']);
            }

            if(Auth::user()->admin_type == 'staff') {
                unset($rules['purchase_date']);
                unset($rules['purchase_price']);
            }

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
            'purchase_date.regex' => 'The date should be in the following format: year-month-day. You can also add ' .
                'hours:minutes or hours:minutes:seconds after a space. Optionally, you can use day/month/year instead of ' .
                'year-month-day.',
        ];
    }
}
