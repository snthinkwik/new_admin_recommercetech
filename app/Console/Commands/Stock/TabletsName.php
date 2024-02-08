<?php namespace App\Console\Commands\Stock;

use App\Stock;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class TabletsName extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'stock:tablets-name';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Remove "Tablets" from item names.';



	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{

		$this->info("Tablets Name");

		$items = Stock::where('name', 'like', '%Tablets%')->limit(25)->get();

		if(!$items) {
			$this->info("No Items");
			return;
		}
		$this->info(count($items)." - Items Found");
		foreach($items as $item) {
			$name = $item->name;
			$new_name = str_replace("Tablets", "", $name);
			$item->name = $new_name;
			$this->info($name." - ".$item->name);
			$item->save();
		}
	}
}
