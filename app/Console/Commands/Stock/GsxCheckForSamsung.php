<?php namespace App\Console\Commands\Stock;

use App\Mobicode\GsxCheck;
use App\Stock;
use App\StockLog;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class GsxCheckForSamsung extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'stock:gsx-check-for-samsung';

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
		$items = Stock::where('make', 'Samsung')->where('status', Stock::STATUS_IN_STOCK)->whereDoesntHave('network_checks')->limit(50)->get();

		if(!count($items)) {
			$this->info('Nothing to Process');
			return;
		}

		foreach($items as $item) {
			$this->info($item->id." ".$item->name." ".$item->imei);
			GsxCheck::create([
				'stock_id' => $item->id,
				'imei' => $item->imei,
				'status' => GsxCheck::STATUS_NEW
			]);
			StockLog::create([
				'stock_id' => $item->id,
				'content' => "Samsung Check Cron - Check Created.",
			]);
		}
	}

}
