<?php namespace App\Console\Commands\Stock;

use App\Stock;
use App\StockLog;
use App\Unlock;
use Illuminate\Console\Command;

class ChangeNetworkUnlockRequested extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'stock:change-network-unlock-requested';

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
		$items = Stock::whereHas('unlock', function($u){
		    $u->where('status', Unlock::STATUS_PROCESSING);
        })->where('network', '!=', 'Unlock Requested')->get();

		if(!count($items)) {
		    $this->info("Nothing to Process.");
		    return;
        }

        $this->info("Items Found: ".$items->count());

		foreach($items as $item) {
		    $this->comment("Item $item->id $item->network, Unlock: ".$item->unlock->status);
		    $item->network = 'Unlock Requested';
		    $item->save();

		    $content = "Network changed from $item->network to 'Unlock Requested'.";
		    StockLog::create([
		        'stock_id' => $item->id,
                'content' => $content
            ]);
        }
	}

}
