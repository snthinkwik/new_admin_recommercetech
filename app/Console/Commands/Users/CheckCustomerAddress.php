<?php namespace App\Console\Commands\Users;

use App\User;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class CheckCustomerAddress extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'users:check-customer-address';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Command description.';

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
		$users = User::where('type', 'user')->has('address')->where('invoice_api_id', '!=', '')->get();

		if(!count($users)) {
			$this->info("Nothing to Process");
			return;
		}

		$updated = 0;

		$this->info("Users Found: ".$users->count());

		$externalIds = $users->lists('invoice_api_id');


		$invoicing = app('App\Contracts\Invoicing');

		$customers = $invoicing->getRegisteredSelectedCustomers($externalIds);

		foreach($users as $user) {
			$this->question("User $user->id $user->email $user->full_name $user->invoice_api_id");
			$customerAddress = $customers->where('external_id', $user->invoice_api_id)->first();
			$address = $user->address;
			if(!$customerAddress) {
				continue;
			}

			$customerAddress = $customerAddress->billing_address;
			if(!$customerAddress) {
				continue;
			}
			if($customerAddress->line1 != $address->line1) {
				$this->comment($customerAddress->line1." - ".$address->line1);
				$address->line1 = $customerAddress->line1;
			}
			if($customerAddress->line2 != $address->line2) {
				$this->comment($customerAddress->line2." - ".$address->line2);
				$address->line2 = $customerAddress->line2;
			}
			if($customerAddress->city != $address->city) {
				$this->comment($customerAddress->city." - ".$address->city);
				$address->city = $customerAddress->city;
			}
			if($customerAddress->country != $address->country) {
				$this->comment($customerAddress->country." - ".$address->country);
				$address->country = $customerAddress->country;
			}
			if($customerAddress->postcode != $address->postcode) {
				$this->comment($customerAddress->postcode." - ".$address->postcode);
				$address->postcode = $customerAddress->postcode;
			}

			if($address->isDirty()) {
				$address->save();
				$this->info("Address Updated");
				$updated++;
			}

		}

		$this->question("Updated: $updated, total users: ".$users->count());

	}

}
