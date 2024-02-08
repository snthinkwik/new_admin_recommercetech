<?php namespace App\Console\Commands\Stock;

use App\Stock;
use App\StockLog;
use App\Unlock;
use App\UnlockMapping;
use Illuminate\Console\Command;
use DB;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class ReadyForSaleUnlocks extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'stock:ready-for-sale-unlocks';

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
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
		$mappingNetworks = UnlockMapping::select('network')->groupBy('network')->get()->lists('network');
		$items = Stock::where('status', Stock::STATUS_READY_FOR_SALE)->where(DB::raw('LENGTH(`imei`)'), 15)->doesntHave('unlock')->where('network', '!=', 'Unlocked')
		->whereIn('network', $mappingNetworks)->orderByRaw('RAND()')->limit(25)->get();
		
		if(!count($items)) {
			$this->info("Nothing to Process");
			return;
		}
		
		$this->info("Items Found: ".$items->count());
		
		foreach($items as $item) {
			$this->info("$item->id $item->make $item->name $item->network");
			if(!$item->getUnlockMapping()) {
				$this->error("Mapping not Found");
				continue;
			} else {
				$this->question(json_encode($item->getUnlockMapping()));
				$unlock = new Unlock();
				$unlock->forceFill([
					'imei' => $item->imei,
					'network' => $item->network,
					'stock_id' => $item->id
				]);
				$unlock->save();
				StockLog::create([
					'stock_id' => $item->id,
					'content' => 'Unlock Created | Cron ReadyForSaleUnlocks'
				]);
			}
		}
	}

}
