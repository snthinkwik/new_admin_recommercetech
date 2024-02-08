<?php namespace App\Console\Commands\Click2Unlock;

use Illuminate\Console\Command;
use Carbon\Carbon;
use Cache;

class GetBalance extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'click2unlock:get-balance';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Command description.';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
		$this->click2unlock = app('App\Contracts\Click2Unlock');
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
		$balance = $this->click2unlock->getBalance();
		if(isset($balance->data->balance)) {
			$balance = (float) $balance->data->balance;
			$this->info("Balance: $balance");
			$expiresAt = Carbon::now()->addMinutes(30);
			Cache::put('click2unlock_balance', $balance, $expiresAt);
		}
	}

}
