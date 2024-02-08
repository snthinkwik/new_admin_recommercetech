<?php namespace App\Console\Commands\Stock;

use App\Stock;
use Illuminate\Console\Command;

class CheckSoldItemsShownTo extends Command {

	protected $name = 'stock:check-sold-items-shown-to';

	protected $description = 'Takes Sold Items where shown_to is not none, and updates them.';

	public function fire()
	{
		$items = Stock::whereNotNull('sale_id')->where('shown_to', '!=', Stock::SHOWN_TO_NONE)->limit(10)->get();

		if(!count($items)) {
			$this->info("Nothing to Process");
			return;
		}

		$this->info("Check Sold Items Shown To: ".$items->count());

		foreach($items as $item) {
			$this->question("$item->id $item->status $item->shown_to");
			$item->shown_to = Stock::SHOWN_TO_NONE;
			$item->save();
			$this->comment("$item->id $item->status $item->shown_to");
		}
	}

}
