<?php namespace App\Console\Commands\Unlocks;

use App\Stock;
use App\Unlock;
use Illuminate\Console\Command;
use DB;

class ReAssignUnlocks extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'unlocks:reassign-unlocks';

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
		$unlocks = Unlock::whereHas('stock_item', function($q) {
			$q->whereIn('status', [Stock::STATUS_PAID, Stock::STATUS_SOLD]);
			$q->whereHas('sale', function($s) {
				$s->where('user_id', '!=', DB::raw('unlocks.user_id'));
			});
		})->orderBy('id', 'desc')
		->limit(50)->get();

		if(!count($unlocks)) {
			$this->info("Nothing to Process");
			return;
		}

		$this->info("Unlocks Found: ".count($unlocks));

		foreach($unlocks as $unlock) {
			$this->info("$unlock->id $unlock->status $unlock->imei $unlock->user_id | $unlock->stock_id ".$unlock->stock_item->status." ".$unlock->stock_item->sale->user_id);
			$this->comment("$unlock->id $unlock->user_id | ".$unlock->stock_item->sale->user_id);
			$unlock->user_id = $unlock->stock_item->sale->user_id;
			$unlock->save();
			$this->comment("$unlock->id $unlock->user_id | ".$unlock->stock_item->sale->user_id);
		}
	}

}
