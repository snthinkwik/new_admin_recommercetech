<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UserFullUpdateRequest extends FormRequest
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
        $rules = [
            'first_name' => 'required',
            'last_name' => 'required',
            'company_name' => 'required',
            'phone' => 'required|max:21',
            'email' => 'required|unique:users,email',
            'business_description' => 'required',
            'whatsapp' => 'required',
            'vat_registered' => 'required',
            'password' => 'required|confirmed|min:6',
            'num_stock_selected' => 'numeric|min:1',
            'address.line1' => 'required',
            'address.city' => 'required',
            'address.county' => 'required',
            'address.postcode' => 'required',
            'address.country' => 'required',
            'terms' => 'required|accepted',
            'location' => 'required|in:' . implode(',', User::getAvailableLocations()),
        ];

        // If someone tries to register as a user that we added as unregistered then we have to remove the email rules,
        // otherwise they won't be able to register. So the "unique:users" rule has to go, but the others can be removed
        // too - if the user exists then their email is already in the system so it's correct.
        if (User::unregistered()->where('email', $this->email)->count() === 1) {
            unset($rules['email']);
        }

        return $rules;
    }
    public function messages()
    {
        return [
            'num_stock_selected.min' => 'Please select at least one.',
            'address.line1.required' => 'Line 1 field is required',
            'address.line2.required' => 'Line 2 field is required',
            'address.city.required' => 'City field is required',
            'address.county.required' => 'County field is required',
            'address.postcode.required' => 'Postcode field is required',
            'address.country.required' => 'Country field is required',
        ];
    }
    public function validate()
    {
        $numStockSelected = 0;
        $stockCheckboxes = ['stock_fully_working', 'stock_minor_fault', 'stock_no_power', 'stock_icloud_locked', 'stock_major_fault'];
        foreach ($stockCheckboxes as $name) {
            if ($this->$name) $numStockSelected++;
        }
        $this->merge(['num_stock_selected' => $numStockSelected]);

        parent::validate();
    }
}
