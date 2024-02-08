<?php namespace App\Console\Commands\Orderhub;

use App\Stock;
use App\StockLog;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class GenerateNewSku extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'orderhub:generate-new-sku';

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
		$items = Stock::where('status', Stock::STATUS_RETAIL_STOCK)->where('name', 'like', "%iphone%")->where('new_sku', '')->limit(100)->orderByRaw('RAND()')->get();
		
		if(!count($items)) {
			$this->info("Nothing to Process");
			return;
		}
		
		$this->info("Items Found: ".$items->count());

		$saved = 0;

		foreach($items as $item) {
			$this->info("$item->id $item->make $item->name $item->colour $item->capacity $item->network $item->grade");
			$this->comment($item->orderhub_new_sku);
			$newSku = $item->orderhub_new_sku;
			if($newSku) {
				$item->new_sku = $newSku;
				$item->save();

				StockLog::create([
					'stock_id' => $item->id,
					'content' => "SKU has been automatically generated: $newSku"
				]);

				$saved++;
				$this->comment("Saved");
			}
			
		}

		$this->question("Saved: $saved / ".count($items));
	}

}
