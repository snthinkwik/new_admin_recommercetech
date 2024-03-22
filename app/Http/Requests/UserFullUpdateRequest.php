<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;


class UserFullUpdateRequest extends UserFullRequest
{

    public function rules()
    {
        $rules = parent::rules();
        $newRules = Arr::only(
            $rules,
            ['phone', 'email', 'address', 'address.line1', 'address.city', 'address.country', 'address.postcode', 'location']
        );
        if ($this->user()->email === $this->email) {
            unset($newRules['email']);
        }

        return $newRules;
    }

}
