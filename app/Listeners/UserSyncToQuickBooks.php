<?php namespace App\Listeners;

use App\Events\EbayOrderUserSyncToQuickBooks;

use App\User;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldBeQueued;
use App\Contracts\Invoicing;

class UserSyncToQuickBooks {

	/**
	 * Create the event handler.
	 *
	 * @return void
	 */
	public function __construct()
	{
		//
	}

	/**
	 * Handle the event.
	 *
	 * @param  EbayOrderUserSyncToQuickBooks  $event
	 * @return void
	 */
	public function handle(EbayOrderUserSyncToQuickBooks $event)
	{

	    $full_name=explode(' ',$event->data->buyer_name);

        if($event->data->post_to_country==="United Kingdom"){

            $customerUser = User::where('invoice_api_id', env('QuickBookEbayUKId'))->firstOrFail();

        }else{
            $customerUser = User::where('invoice_api_id', env('QuickBookEbayEUId'))->firstOrFail();
        }


        if($event->data->post_to_country ==="United Kingdom"){
            $shippingCountry= str_replace(" ","",$event->data->post_to_country);
        }else{
            $shippingCountry=$event->data->post_to_country;
        }
        if($event->data->buyer_country==="United Kingdom"){
            $billingCountry=str_replace(" ","",$event->data->buyer_country);
        }else{
            $billingCountry=$event->data->buyer_country;
        }


        $customer = $customerUser->getCustomer($event->invoiceApiId);
        $customer->first_name=isset($full_name[0])?$full_name[0]:'';
        $customer->last_name=isset($full_name[1])?$full_name[1]:'';
       // $customer->company_name=$event->data->shipping_address_company_name;
        $customer->shipping_address->line1=$event->data->post_to_address_1;
        $customer->shipping_address->line2=$event->data->post_to_address_2;
        $customer->shipping_address->city=$event->data->post_to_city;
        $customer->shipping_address->county=$event->data->post_to_county;
        $customer->shipping_address->postcode=$event->data->post_to_postcode;
        $customer->shipping_address->country=$shippingCountry;

        $customer->billing_address->line1=$event->data->buyer_address_1;
        $customer->billing_address->line2=$event->data->buyer_address_2;
        $customer->billing_address->city=$event->data->buyer_city;
        $customer->billing_address->county=$event->data->buyer_county;
        $customer->billing_address->postcode=$event->data->buyer_postcode;
        $customer->billing_address->country=$billingCountry;

        $event->invoicing->updateCustomer($customer);
	}

}
