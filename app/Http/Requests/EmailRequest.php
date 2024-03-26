<?php

namespace App\Http\Requests;

use App\Models\Email;
use Illuminate\Foundation\Http\FormRequest;

class EmailRequest extends FormRequest
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
            'subject' => 'required',
            'body' => 'required',
            'from_email' => 'required|email',
            'from_name' => 'required',
        ];

        if ($this->attachment === Email::ATTACHMENT_FILE) {
            $rules['file'] = 'required';
        }

        return $rules;
    }
}
