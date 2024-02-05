<?php namespace App\Http\Requests;

use App\Http\Requests\Request;



class ImeiRequest extends Request
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

    public function rules()
    {
        $rules = [
            'imeis' => 'required|array|min:1'
        ];

        foreach ($this->imeis as $i => $imei) {
            $rules["imeis.$i"] = 'regex:/^\d{15,16}$/';
        }

        return $rules;
    }

    protected function getValidatorInstance()
    {
        $validator = parent::getValidatorInstance();

        $validator->after(function($validator)
        {

            $errors = $validator->errors();
            foreach ($errors->getMessages() as $k => $error) {
                if (preg_match('/^imeis\.\d+$/', $k)) {
                    $errors->add(
                        'imeis',
                        preg_replace_callback(
                            '/ imeis\.(\d+) /',
                            function($m) {
                                return ' imei #' . ($m[1] + 1) . ' ';
                            },
                            $error[0]
                        )
                    );
                }
            }
        });

        return $validator;
    }

    public function validate()
    {

        $imeis = preg_split('/[\s,]+/', $this->imeis_list, -1, PREG_SPLIT_NO_EMPTY);
        $this->merge(['imeis' => $imeis]);
        parent::validate();
    }
}
