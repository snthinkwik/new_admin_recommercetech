<?php namespace App\Console\Commands\Stock;

use App\Mobicode\GsxCheck;
use App\Stock;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class CreateIpadNetworkIcloudCheck extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'stock:create-ipad-network-icloud-check';

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
		$items = Stock::whereIn('status', [Stock::STATUS_BATCH, Stock::STATUS_IN_STOCK])->where('name', 'like', "%ipad%")->where('make', 'Apple')->where(function($q){
			$q->where('imei', '!=', '');
			$q->orWhere('serial', '!=', '');
		})->doesntHave('icloud_status_check')->doesntHave('network_checks')->orderBy('id', 'desc')->limit(10)->get();

		if(!count($items)) {
			$this->info('Nothing to Process');
			return;
		}

		$this->info('Items Found: '.$items->count());

		foreach($items as $item) {
			$this->info("$item->id | $item->imei | $item->serial");
			if($item->imei) {
				GsxCheck::create([
					'stock_id' => $item->id,
					'imei' => $item->imei,
					'status' => GsxCheck::STATUS_NEW // network check
				]);
			} elseif($item->serial) {
				GsxCheck::create([
					'stock_id' => $item->id,
					'imei' => $item->serial,
					'status' => GsxCheck::STATUS_NEW,
					'service_id' => GsxCheck::SERVICE_ICLOUD_STATUS_CHECK // icloud status check
				]);
			}
		}
	}

}
