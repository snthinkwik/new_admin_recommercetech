<?php namespace App\Console\Commands\Stock;

use App\LockCheck;
use App\Stock;
use DB;
use Illuminate\Console\Command;

class PlaceLockCheckOrder extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'stock:place-lock-check-order';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Places CMN Lock Check Orders for items with Lock Check status=New';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		$this->click2unlock = app('App\Contracts\Click2Unlock');
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
		$lockChecks = LockCheck::where('status', LockCheck::STATUS_NEW)->limit(10)->get();
		if(!count($lockChecks)) {
			$this->info("Nothing to Process");
			return;
		}

		$this->info("Place Lock Check Orders: ".$lockChecks->count());

		foreach($lockChecks as $lockCheck) {
			$this->question("LockCheck $lockCheck->id $lockCheck->imei");
			$imei = $lockCheck->imei;

			$response = $this->click2unlock->lockCheck($imei);
			$this->info(json_encode($response));
			if($response->status) {
				if($response->status == "success") {
					$lockCheck->status = LockCheck::STATUS_PROCESSING;
					$lockCheck->external_id = $response->data->check_id;
					$lockCheck->response = json_encode($response);
					$lockCheck->save();
				} else {
					$lockCheck->status = LockCheck::STATUS_ERROR;
					$lockCheck->response = json_encode($response);
					$lockCheck->report = $response->data;
					$lockCheck->save();
				}
			} else {
				$this->comment("Error: ".json_encode($response));
			}
		}
	}

}
