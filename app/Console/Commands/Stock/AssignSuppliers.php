<?php namespace App\Console\Commands\Stock;

use App\Stock;
use App\StockLog;
use App\Supplier;
use Illuminate\Console\Command;
use DB;

class AssignSuppliers extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'stock:assign-suppliers';

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
		$items = Stock::whereNull('supplier_id')->where(function($q){
			$q->where('third_party_ref', 'like', "T0000%"); // Music Magpie
			$q->orWhere(DB::raw('LENGTH(`third_party_ref`)'), 7); // Mazuma
		})->limit(100)->get();

		if(!count($items)) {
			$this->info('Nothing to Process');
			return;
		}

		$mazuma = Supplier::where('name', 'Mazuma')->firstOrFail();
		$musicMagpie = Supplier::where('name', 'Music Magpie')->firstOrFail();

		foreach($items as $item) {
			$this->info($item->id." - ".$item->third_party_ref);
			if(substr($item->third_party_ref, 0, 5) == 'T0000') {
				$item->supplier_id = $musicMagpie->id; // Music Magpie
				$item->save();
				$this->question('Music Magpie');
				StockLog::create([
					'stock_id' => $item->id,
					'content' => 'Assigned Supplier Music Magpie - Cron Assigning Suppliers'
				]);
			} elseif(strlen($item->third_party_ref) == 7 && is_numeric($item->third_party_ref)) {
				$item->supplier_id = $mazuma->id; // Mazuma
				$item->save();
				$this->question('Mazuma');
				StockLog::create([
					'stock_id' => $item->id,
					'content' => 'Assigned Supplier Mazuma - Cron Assigning Suppliers'
				]);
			}
		}
	}

}
