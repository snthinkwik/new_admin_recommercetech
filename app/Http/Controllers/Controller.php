<?php namespace App\Http\Controllers;
use App\Exceptions\ApiException;
use App\Validation\Validator;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Validate the given request with the given validator object.
     *
     * @param Validator $validator
     * @return array Validator's data
     */
    public function validateWithObject(Request $request, Validator $validator)
    {

        if ($validator->fails())
        {
            $this->throwValidationException($request, $validator);
        }

        return $validator->getData();
    }

    /**
     * Validate the given request and return API errors when validation fails.
     *
     * @param Validator $validator
     * @return array Validator's data
     */
    public function validateAsApi(Validator $validator)
    {
        if ($validator->fails()) {
            foreach ($validator->errors()->getMessages() as $field => $errors) {
                throw new ApiException($errors[0]);
            }
        }

        return $validator->getData();
    }
}
