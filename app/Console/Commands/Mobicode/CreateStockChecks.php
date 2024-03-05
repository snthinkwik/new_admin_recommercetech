<?php namespace App\Console\Commands\Mobicode;

use App\Models\Mobicode\GsxCheck;
use App\Models\Stock;
use App\Models\StockLog;
use Illuminate\Console\Command;
use DB;

class CreateStockChecks extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'mobicode:create-stock-checks';

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
	public function handle()
	{
		$items = Stock::/*where('network', 'Unknown')
			->*/whereDoesntHave('network_checks', function($q) {
				$q->where('service_id', GsxCheck::SERVICE_APPLE_BASIC_CHECK);
			})
			->where('name', 'like', '%iphone%')
			->whereNotNull('imei')
			->where(DB::raw('LENGTH(`imei`)'), 15)
			->whereIn('status', [Stock::STATUS_IN_STOCK, Stock::STATUS_BATCH, Stock::STATUS_INBOUND, Stock::STATUS_READY_FOR_SALE])
			->limit(50)->orderByRaw('RAND()')->get();

		if(!count($items)) {
			$this->info("Nothing to Process");
			die;
		}

		$this->comment('Stock Items Found: '.count($items));

		foreach($items as $item) {
			$this->question("$item->id $item->imei $item->name $item->network");
			if($item->network_checks()->where('service_id', GsxCheck::SERVICE_APPLE_BASIC_CHECK)->count()) {
				// check already exists
				$this->comment('check already exists');
				continue;
			}

			$gsxCheck = GsxCheck::create([
				'stock_id' => $item->id,
				'imei' => $item->imei,
				'status' => GsxCheck::STATUS_NEW,
				'service_id' => GsxCheck::SERVICE_APPLE_BASIC_CHECK
			]);
			$this->comment('Check created - '.$gsxCheck->id);
			StockLog::create([
				'stock_id' => $item->id,
				'content' => 'Network Check Created | Cron: create-stock-checks'
			]);
		}
	}

}
