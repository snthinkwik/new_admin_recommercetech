<?php namespace App\Observers;

use App\Commands\Users\EmailSend;
use App\Commands\Users\NewRegistration;
use App\Customer;
use App\User;
use Exception;
use Illuminate\Events\Dispatcher;
use Queue;

class UserObserver {
	
	public function onUserRegister($event)
	{
		$user = $event->user;
		Queue::pushOn('emails', new NewRegistration($user));
		$isRegular = !$user->type || $user->type === 'user';
		if ($isRegular && !$user->invoice_api_id) {
			$invoicing = app('App\Contracts\Invoicing');
			$customer = $user->getCustomer();
			$attempts = 0;
			do {
				$attempts++;
				try {
					$user->invoice_api_id = $invoicing->createCustomer($customer);
					break;
				}
				catch (Exception $e) {
					if ($attempts <= 3 && strpos($e, 'The name supplied already exists') !== false) {
						preg_match('/ \((\d+)\)$/', $customer->last_name, $dupeIdxMatch);
						$dupeIdx = $dupeIdxMatch ? $dupeIdxMatch[1] + 1 : 2;
						$customer->last_name = preg_replace('/ \(\d+\)$/', '', $customer->last_name);
						$customer->last_name .= " ($dupeIdx)";
					}
					else {
						throw $e;
					}
				}
			}
			while (true);
			$user->save();
			Queue::pushOn('emails', new EmailSend($user, EmailSend::TYPE_REGISTERED));
			if(substr($user->phone, 0, 2) == "07" || substr($user->phone,0, 3) == "447" || substr($user->phone,0,5) == "00447") {
				$txtlocal = app('App\Contracts\Txtlocal');
				$sms = $txtlocal->send($user->phone);
			}
		}
	}
	
	/**
	 * Register the listeners for the subscriber.
	 *
	 * @param  Dispatcher  $events
	 * @return void
	 */
	public function subscribe(Dispatcher $events)
	{
		$events->listen('App\Events\User\Registered', 'App\Observers\UserObserver@onUserRegister');
	}
	
}