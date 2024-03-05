<?php namespace App\Console\Commands\Stock;

use App\Repair;
use App\RepairEngineer;
use App\RepairStatus;
use App\RepairType;
use App\Stock;
use App\StockLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class CreateMissingRepairs extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'stock:create-missing-repairs';

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
		$items = Stock::whereHas('stockLogs', function($q) {
			$q->where(function($w){
				$w->where('content', 'like', "Scan - set status - In Repair |%");
				$w->orWhere('content', 'like', "%Status changed from % to 'In Repair'%");
				$w->orWhere('content', 'like', "%Changed \"status\" from % to \"In Repair\"%");
				$w->orWhere('content', 'like', "%Changed \"status\" from \"In Repair\" to %");
				$w->orWhere('content', 'like', "Status changed from 'In Repair' to %");
				$w->orWhere('content', 'like', "%repair has been closed%");
			});
		})->doesntHave('repairs')->limit(25)->orderBy('id', 'desc')->get();
		
		if(!count($items)) {
			$this->info("Nothing to Process");
			return;
		}
		$found = $items->count();
		$correctStatus = 0;
		$wrongStatus = 0;
		$wrongStatuses = [];
		$this->info("Found: ".$items->count());
		
		$engineer = RepairEngineer::where('name', 'Izzy')->firstOrFail();
		$this->info("Engineer: $engineer->id $engineer->name");
		
		foreach($items as $item) {
			$this->question("Item $item->id $item->status");
			
			$logsInRepair = StockLog::where('stock_id', $item->id)->where(function($q) {
				$q->where('content', 'like', "Scan - set status - In Repair |%");
				$q->orWhere('content', 'like', "%Status changed from % to 'In Repair'%");
				$q->orWhere('content', 'like', "%Changed \"status\" from % to \"In Repair\"%");
				$q->orWhere('content', 'like', "%Changed \"status\" from \"In Repair\" to %");
				$q->orWhere('content', 'like', "Status changed from 'In Repair' to %");
				$q->orWhere('content', 'like', "%repair has been closed%");
			})->orderBy('id', 'asc')->get();
			
			$previousOpenRepair = null;
			
			foreach($logsInRepair as $log) {
				$this->info($log->created_at->format('d/m/y H:i:s')." | ".$log->content);
				// new repair
				if(
					strpos(strtolower($log->content), 'scan - set status - in repair') !== false ||
					strpos(strtolower($log->content), 'to "in repair"') !== false ||
					strpos(strtolower($log->content), "to 'in repair'") !== false
				) {
					$repair = new Repair();
					$repair->item_id = $item->id;
					$repair->status = RepairStatus::STATUS_OPEN;
					$repair->engineer = $engineer->id;
					$repair->type = RepairType::TYPE_LEVEL_1;
					$repair->created_at = $log->created_at;
					$repair->save();
					$this->comment("Repair Created - New");
					$previousOpenRepair = $repair;
				} elseif(
					strpos(strtolower($log->content), 'from "in repair"') !== false ||
					strpos(strtolower($log->content), "from 'in repair'") !== false ||
					strpos(strtolower($log->content), 'repair has been closed') !== false
				) {
					if($previousOpenRepair) {
						$repair = $previousOpenRepair;
						$repair->closed_at = $log->created_at;
						$repair->status = RepairStatus::STATUS_CLOSED;
						$repair->save();
						$previousOpenRepair = null;
						$this->comment("Repair Closed");
					}
				}
			}
			if($previousOpenRepair && $previousOpenRepair->status == RepairStatus::STATUS_OPEN && $item->status != Stock::STATUS_REPAIR) {
				$this->info("Open Repair - Status not In Repair");
				$log = StockLog::where('stock_id', $item->id)->where('created_at', '>', $previousOpenRepair->created_at)->where(function($q) {
					$q->where('content', 'like', "%from Inbound%");
					$q->orWhere('content', 'like', "%Inbound%");
				})->first() ;
				if($log) {
					$repair = $previousOpenRepair;
					$repair->status = RepairStatus::STATUS_CLOSED;
					$repair->closed_at = $log->created_at;
					$repair->save();
					$previousOpenRepair = null;
					$this->comment("Repair Closed - Inbound Log");
					$correctStatus++; 
				} else {
					$wrongStatuses[] = $item->id;
					$wrongStatus++;
				}
			} else {
				$correctStatus++;
			}
		}
		
		$this->question("Total: $found");
		$this->question("Correct Status: $correctStatus");
		$this->question("Wrong Status: $wrongStatus");
		
		$this->info("Wrong Statuses");
		$this->info(json_encode($wrongStatuses));
	}

}
