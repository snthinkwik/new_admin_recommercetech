<?php namespace App\Validation\Unlocks;

use App\Unlock\Pricing;
use App\Validation\ImeiValidator;

class OrderValidator extends ImeiValidator {

	protected $imeis;

	public function rules()
	{
		$rules = parent::rules();

		$rules = $rules + [
			'network' => 'required|in:' . implode(',', Pricing::getAvailableNetworks()),
			'models' => 'required|in:' . implode(',', Pricing::getAvailableModels()),
		];

		return $rules;
	}

}