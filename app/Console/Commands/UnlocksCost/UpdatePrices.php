<?php namespace App\Console\Commands\UnlocksCost;

use App\UnlockCost;
use App\UnlockMapping;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class UpdatePrices extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'unlocks-cost:update-prices';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Command description.';

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
		// $prices = UnlockCost::whereNotNull('service_id')->where('service_id', '>', 0)->get();
		$prices = UnlockMapping::whereNotNull('service_id')->where('service_id', '>', 0)->get();

		if(!count($prices)) {
			$this->info("Nothing to Process");
			return;
		}

		$this->info("Prices: ".$prices->count());

		$res = $this->click2unlock->getServices();
		$services = [];
		if($res->status == "success") {
			$data = $res->data;
			foreach($data as $d) {
				if($d->type == "Unlock" && in_array($d->id, $prices->lists('service_id'))) {
					$services[$d->id] = $d;
				}
			}
		}

		if(!count($services)) {
			$this->comment("No Matching Services");
			return;
		}

		foreach($prices as $price) {
			$this->info("$price->network $price->service_id $price->cost");
			if(isset($services[$price->service_id]->price)) {
				$price->cost = $services[$price->service_id]->price;
				$this->comment($price->cost);
				$price->save();
				$this->question("Price Updated");
			}

		}
	}

}
