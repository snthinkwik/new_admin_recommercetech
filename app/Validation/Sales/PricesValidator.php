<?php namespace App\Validation\Sales;

use App\Models\Stock;
use App\Validation\Validator;
use Illuminate\Support\Facades\Auth;
use  Illuminate\Contracts\Translation\Translator;


class PricesValidator extends Validator {

	public function __construct(Translator $translator, array $data, array $rules = array(), array $messages = array(), array $customAttributes = array())
	{

		$user = Auth::user();

		// Regular users have to have invoicing API id.

		if ($user->type === 'user') {
			$data['user_invoice_api_id'] = $user->invoice_api_id;
			if (!$user->invoice_api_id) {
				alert("User $user->full_name ($user->id) has empty invoice API id.");
			}
		}

		if(!is_null(Auth::user()->part_basket)) {
			$this->items = isset($data['items']) ? $data['items'] : [];
		} else {
            $this->items = isset($data['items']) ? $data['items'] : [];
		}
		parent::__construct($translator, $data, $rules, $messages, $customAttributes);
	}

	public function rules()
	{
		$user = Auth::user();
		$rules = [];
		$stock = Stock::whereIn('id', array_keys($this->items))->get()->keyBy('id');

		foreach (array_keys($this->items) as $i) {
			if (!isset($stock[$i]) || !$stock[$i]->sale_price) {
				$rules["items.$i.price"] = 'required';
			}
		}

		if ($user->type === 'user') {
			$rules['user_invoice_api_id'] = 'required';
		}

		return $rules;
	}

	public function customMessages()
	{
		$messages = [];

		foreach (array_keys($this->items) as $i) {
			$messages["items.$i.price.required"] = 'Price is required.';
			$messages["items.$i.price.numeric"] = 'Price must be a numeric value.';
		}

		$messages['user_invoice_api_id.required'] = "Unexpected error occurred. You won't be able to create orders at " .
			"this time. We've been notified and we'll try to resolve the issue as soon as possible.";

		return $messages;
	}

}
