<?php namespace App\Validation;

use  Illuminate\Contracts\Translation\Translator;


class ImeiValidator extends Validator {

	protected $imeis;

	public function __construct(Translator $translator, array $data, array $rules = array(), array $messages = array(), array $customAttributes = array())
	{


		$this->imeis = isset($data['imeis_list'])
			? $data['imeis'] = preg_split('/[\s,]+/', $data['imeis_list'], -1, PREG_SPLIT_NO_EMPTY)
			: [];

		parent::__construct($translator, $data, $rules, $messages, $customAttributes);

		$this->after(function ($validator)
		{
			$errors = $validator->errors();

			foreach ($this->imeis as $i => $imei) {
				if (!preg_match('/^\d{15,16}$/', $imei)) {
					$errors->add('imeis', 'The IMEI #' . ($i + 1) . ' format is invalid');
				}
			}
		});
	}

    /**
     * @return string[]
     */
	public function rules()
	{
		$rules = [
			'imeis' => 'required|array|min:1',
		];

		return $rules;
	}

}
