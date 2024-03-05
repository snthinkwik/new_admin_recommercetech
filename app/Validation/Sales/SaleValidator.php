<?php namespace App\Validation\Sales;

use App\Sale;
use App\Stock;
use Illuminate\Support\Facades\Auth;
use  Illuminate\Contracts\Translation\Translator;


class SaleValidator extends PricesValidator {

	public function __construct(Translator $translator, array $data, array $rules = array(), array $messages = array(), array $customAttributes = array())
	{
		if(count(Auth::user()->part_basket)>0) {
			if (isset($data['items'])) {
				$this->items = $data['items'];
				$this->stock = Stock::whereIn('id', array_keys($data['items']))->get()->keyBy('id');
				$orderAmount = 0;

				// Add item statuses to the request so that they can be compared to what's allowed for the user.
				$items = $data['items'];
				foreach ($items as $id => $item) {
					$items[$id]['status'] = $this->stock[$id]->status;
					$orderAmount += $this->stock[$id]->sale_price * 100;
				}

				$data['items'] = $items;
				$data['order_amount'] = $orderAmount / 100;
			}
		} else {
			$this->items = $data['items'];
			$this->stock = Stock::whereIn('id', array_keys($data['items']))->get()->keyBy('id');
			$orderAmount = 0;

			// Add item statuses to the request so that they can be compared to what's allowed for the user.
			$items = $data['items'];
			foreach ($items as $id => $item) {
				$items[$id]['status'] = $this->stock[$id]->status;
				$orderAmount += $this->stock[$id]->sale_price * 100;
			}

			$data['items'] = $items;
			$data['order_amount'] = $orderAmount / 100;
		}
		// Regular users aren't allowed to select customer. They make orders for themselves.
		if (Auth::user()->type === 'user') {
			$data['customer_external_id'] = Auth::user()->invoice_api_id;
			$data['customer_is_collecting'] = false;
		}

		if(isset($data['recycler'])) {
			$this->recycler = $data['recycler'];
		}

		parent::__construct($translator, $data, $rules, $messages, $customAttributes);
	}

	public function rules()
	{
		$rules = parent::rules();

		if(!isset($this->recycler))
			$rules['customer_external_id'] = 'required';

		foreach (array_keys($this->items) as $id) {
			if(Auth::user()->type === 'user')
				$rules["items.$id.price"] = 'in:' . $this->stock[$id]->sale_price;
		}

		foreach (array_keys($this->items) as $id) {
			$rules["items.$id.status"] = 'in:' . implode(',', Auth::user()->allowed_statuses_buying);
		}

		return $rules;
	}

	public function customMessages()
	{
		$messages = parent::customMessages();
		$messages['customer_external_id.required'] = 'Customer id is required.';

		if (Auth::user()->type === 'user') {
			foreach (array_keys($this->items) as $id) {
				$messages["items.$id.price.in"] = "It looks like the price has changed since you opened the sale page.";
			}
		}

		foreach (array_keys($this->items) as $id) {
			$messages["items.$id.status.in"] = "Status of the item {$this->stock[$id]->our_ref} has changed to " .
				"{$this->items[$id]['status']}.";
		}

		return $messages;
	}

}
