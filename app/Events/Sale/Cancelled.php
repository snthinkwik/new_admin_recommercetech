<?php namespace App\Events\Sale;

use App\Events\Event;

use App\Models\Sale;
use Illuminate\Queue\SerializesModels;

class Cancelled extends Event {

	use SerializesModels;

	/**
	 * @var Sale
	 */
	public $sale;

	public function __construct(Sale $sale)
	{
		$this->sale = $sale;
	}

}
