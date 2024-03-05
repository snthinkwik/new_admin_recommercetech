<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
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
            'email' => 'required|unique:users,email',
        ];
        // For existing users allow their current email address, otherwise they'll get an error if they don't want to
        // change it.
        if ($this->id) {
            $rules['email'] .= ',' . $this->id;
        }
        // For new users make the password required. For existing users the input can be empty, meaning "leave as is".
        else {
            $rules['password'] = 'required';
        }

        if ($this->type === 'user') {
            $rules['invoice_api_id'] = 'required';
        }

        return $rules;
    }

    public function validate()
    {
        // If someone cleared the name, we should clear the id too, indicating they want to remove customer association.
        if (!$this->invoice_api_id_name) {
            $this->merge(['invoice_api_id' => '']);
        }
        parent::validate();
    }
}
