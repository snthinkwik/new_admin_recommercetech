<?php namespace App\Console\Commands\Stock;

use App\LockCheck;
use App\Stock;
use DB;
use Illuminate\Console\Command;

class CreateLockChecks extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'stock:create-lock-checks';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'For Stock Items with no Lock Check - creates Lock Check order.';

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
		$items = Stock::whereIn('status', [Stock::STATUS_IN_STOCK, Stock::STATUS_INBOUND])->where('name', 'like', "iphone%")->where(DB::raw('LENGTH(`imei`)'), 15)->has('lock_check', '=', 0)->limit(10)->get();

		if(!count($items)) {
			$this->info("Nothing to Process");
			return;
		}

		$this->info("Place Lock Check Orders: ".$items->count());

		foreach($items as $item) {
			$this->question("Stock $item->id $item->imei $item->name");
			$imei = $item->imei;

			$lockCheck = new LockCheck();
			$lockCheck->status = LockCheck::STATUS_NEW;
			$lockCheck->imei = $imei;
			$lockCheck->stock_id = $item->id;
			$lockCheck->save();
		}
	}

}
