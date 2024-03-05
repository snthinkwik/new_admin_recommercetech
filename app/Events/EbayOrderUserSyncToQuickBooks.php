<?php namespace App\Events;

use App\Events\Event;

use Illuminate\Queue\SerializesModels;


class EbayOrderUserSyncToQuickBooks extends Event {

	use SerializesModels;

	/**
	 * Create a new event instance.
	 *
	 * @return void
	 */
	public $invoiceApiId;
	public $data;
	public $invoicing;
	public function __construct($invoiceApiId,$data,$invoicing)
	{
	    $this->invoiceApiId=$invoiceApiId;
	    $this->data=$data;
	    $this->invoicing=$invoicing;



	}

}
