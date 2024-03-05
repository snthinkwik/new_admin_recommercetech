<?php namespace App\Console\Commands\Stock;

use App\Stock;
use App\StockLog;
use Illuminate\Console\Command;

class TryFixMissingMake extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'stock:try-fix-missing-make';

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
		$items = Stock::where('make', '')->where(function($query) {
			$query->where('name', 'like', '%iphone %');
			$query->orWhere('name', 'like', '%ipad %');
			$query->orWhere('name', 'like', '%galaxy%');
			$query->orWhere('name', 'like', '%S5 Neo%');
		})->limit(100)->orderBy('id', 'desc')->get();

		if(!count($items)) {
			$this->info("Nothing to Process");
			return;
		}

		foreach($items as $item) {
			if(strpos(strtolower($item->name), 'iphone') !== false || strpos(strtolower($item->name), 'ipad') !== false) {
				$this->info($item->id." | ".$item->name." - Apple");
				$item->make = 'Apple';
				$item->save();
				StockLog::create([
					'stock_id' => $item->id,
					'content' => "Make $item->make updated - Cron matching makes"
				]);
			} elseif(strpos(strtolower($item->name), 'galaxy') !== false || strpos(strtolower($item->name), 's5 neo') !== false) {
				$this->info($item->id." | ".$item->name." - Samsung");
				$item->make = 'Samsung';
				$item->save();
				StockLog::create([
					'stock_id' => $item->id,
					'content' => "Make $item->make updated - Cron matching makes"
				]);
			}
		}

	}

}
