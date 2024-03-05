<?php namespace App\Observers;

use App\Jobs\Sales\EmailSend;
use App\Jobs\Sales\EmailSend2;
use Illuminate\Events\Dispatcher;
use Queue;

class SaleObserver {

	public function onSaleCancelled($event)
	{

       $inoicing= app('App\Contracts\Invoicing');
        $customer = $inoicing->getCustomer($event->sale->customer_api_id);
        $newCustomer=[];
        if(!is_null($customer)){
            $newCustomer=[
                'first_name'=>$customer->first_name,
                'last_name'=>$customer->last_name,
                'email'=>$customer->email
            ];
        }
        dispatch(new EmailSend($event->sale,EmailSend::EMAIL_CANCELLED,$newCustomer));

	}

	public function subscribe(Dispatcher $events)
	{
		$events->listen('App\Events\Sale\Cancelled', 'App\Observers\SaleObserver@onSaleCancelled');
	}

}
