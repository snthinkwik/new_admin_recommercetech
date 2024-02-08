<?php namespace App\Console\Commands\Stock;

use App\Product;
use App\Stock;
use App\StockLog;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class MapProducts extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'stock:map-products';

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
        ini_set('memory_limit', '1024M');
		$missing = [];

		$found = 0;

		$items = Stock::where('name', '!=', '')->whereNull('product_id')->orderByRaw('RAND()')->get();


		foreach($items as $item) {
			//$this->info($item->id ." | ".$item->name." ".$item->capacity_formatted);
			$products = Product::where('non_serialised','0')->where(function($m) use($item) {
//				$m->where('model', $item->name." ".$item->capacity_formatted);
//				$m->orWhere('model', $item->name." - ".$item->capacity_formatted);
//				$m->orWhere('model', $item->name." ".$item->capacity." GB");
//				$m->orWhere('model', $item->name." - ".$item->capacity." GB");
				$m->where('product_name', $item->name." ".$item->capacity_formatted);
				$m->orWhere('product_name', "$item->make $item->name $item->capacity_formatted");
				$m->orWhere('product_name', "$item->make ".str_replace("+", " Plus", $item->name)." $item->capacity_formatted");

			})->get();
			if(count($products) == 1) {
				$product = $products->first();

				$item->product_id = $product->id;
				$item->save();

				StockLog::create([
					'stock_id' => $item->id,
					'content' => 'Assigned TRG Product: '.$product->id.' - Map Products Cron'
				]);

				$this->comment("Assigned TRG Product $product->id $product->make $product->model | $item->make $item->name $item->capacity_formatted");
				$found++;
			} elseif(count($products) > 1) {
				$this->question("Multiple products found: $item->name $item->capacity_formatted");
			} else {
				$longName = $item->name." ".$item->capacity_formatted;
				if(isset($missing[$longName])) {
					$missing[$longName] = $missing[$longName]+1;
				} else {
					$missing[$longName] = 1;
				}
			}
		}

		$this->question("Found: $found");

		//dd($missing);
	}

}
