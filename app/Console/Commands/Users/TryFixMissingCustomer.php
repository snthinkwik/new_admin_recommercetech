<?php namespace App\Console\Commands\Users;

use App\Address;
use App\User;
use Exception;
use Illuminate\Console\Command;

class TryFixMissingCustomer extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'users:try-fix-missing-customer';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Creates customers for users with address but no quickbooks customer assigned..';

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
		$users = User::where('invoice_api_id', "")->where('type', 'user')->has('address')->orderBy('id', 'desc')->limit(25)->get();

		if(!count($users)) {
			$this->info("Nothing to Process.");
			return;
		}

		$this->info("Users - Try Fix Missing Customers: ".$users->count());

		foreach($users as $user) {
			$this->question("$user->id $user->full_name $user->invoice_api_id");

			$invoicing = app('App\Contracts\Invoicing');

			$user->address->fill(convert_special_characters($user->address->toArray()));
			$user->address->save();

			$customer = $invoicing->getCustomers()->where('email', $user->email)->first();
			if ($customer) {
				$user->invoice_api_id = $customer->external_id;
				$user->save();
			}

			$isRegular = !$user->type || $user->type === 'user';
			if ($isRegular && !$user->invoice_api_id) {
				$customer = $user->getCustomer();
				$attempts = 0;
				do {
					$attempts++;
					try {
						$user->invoice_api_id = $invoicing->createCustomer($customer);
						$user->save();
						break;
					} catch (Exception $e) {
						if ($attempts <= 3 && strpos($e, 'The name supplied already exists') !== false) {
							preg_match('/ \((\d+)\)$/', $customer->last_name, $dupeIdxMatch);
							$dupeIdx = $dupeIdxMatch ? $dupeIdxMatch[1] + 1 : 2;
							$customer->last_name = preg_replace('/ \(\d+\)$/', '', $customer->last_name);
							$customer->last_name .= " ($dupeIdx)";
						} elseif($attempts > 3) {
							print($e->getMessage());
							break;
						}
					}
				} while (true);
			}
			$this->comment("User $user->id $user->full_name Customer ID: $user->invoice_api_id");

		}
	}

}
