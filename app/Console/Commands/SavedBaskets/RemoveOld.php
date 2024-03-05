<?php namespace App\Console\Commands\SavedBaskets;

use App\SavedBasket;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class RemoveOld extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'saved-baskets:remove-old';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Removes 48h old baskets. admin_recommercetech#22';

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
		$subDate = Carbon::now()->subHours(48);
		$this->info($subDate);
		$baskets = SavedBasket::where('created_at', '<=', $subDate)->get();
		if(!count($baskets)) {
			$this->info("Nothing to Process");
			return;
		}
		
		$this->info("Baskets Found: ".$baskets->count());
		
		foreach($baskets as $basket) {
			$this->question("$basket->id ".$basket->created_at->format('d/m/Y H:i:s')." | ".$basket->created_at->diffInHours());
			$basket->delete();
		}
	}

}
