<?php namespace App\Console\Commands\Mobicode;

use App\Colour;
use App\Mobicode\GsxCheck;
use App\Stock;
use App\StockLog;
use App\Support\ReportParser;
use Exception;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class ProcessGSXcheck extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'mobicode:process-gsx-checks';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Takes New gsx checks and creates request.';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
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
		$checks = GsxCheck::where('status', GsxCheck::STATUS_DONE)->where('processed', false)->where('external_id','!=',0)
			->where(function($query) {
				$query->where(function($q){
					$q->whereNotNull('stock_id');
				});
				$query->orWhere(function($q){
					$q->whereNotNull('unlock_id');
				});
			})->limit(25)->orderByRaw('RAND()')->get();

		$this->info("GsxCheck Processing: ".count($checks)." checks");

		if(!count($checks)) {
			$this->info("Nothing to Process");
			return;
		}

		$done=0;
		$total = count($checks);

		foreach($checks as $check) {
			try {
				if($check->stock_id && $check->service_id == GsxCheck::SERVICE_CAPACITY_COLOUR_CHECK) {
					$this->parseCapacityColourReport($check);
				} elseif($check->stock_id) {
					$this->parseReport($check);
				} elseif($check->unlock_id) {
					$this->parseUnlockReport($check);
				}
				$check->processed = true;
				$check->save();

			} catch(Exception $e) {
				alert("Process GSX Check Exception: ".$e);
				$this->question("Check ".$check->id." Exception");
				$check->status = GsxCheck::STATUS_ERROR;
				$check->response = $e->getMessage();
				$check->save();
				if($check->stock_id) {
					StockLog::create([
						'user_id' => $check->user ? $check->user->id : null,
						'stock_id' => $check->stock->id,
						'content' => "GSX Check Report Error (Process GSX Check Report Cron)",
					]);
				}
				if($check->unlock_id) {
					$unlock = $check->unlock;
					$unlock->check_status = GsxCheck::STATUS_ERROR;
					$unlock->save();
				}
			}
			$done++;
			progress($done, $total);
		}
	}

	protected function parseReport($check) {
		$stock = $check->stock;
		$report = $check->report;

		if(preg_match('/SIM Lock\s*:\s+(?<locked>.*?)(\.|\s*<br>)/', $report, $locked) && isset($locked['locked'])) {
			$locked = $locked['locked'];
		} elseif(preg_match('/Is Network Locked\s*:\s+(?<locked>.*?)(\.|\s*<br>)/', $report, $locked) && isset($locked['locked'])) {
			$locked = $locked['locked'];
		}

		preg_match('/Registered Purchase Date:\s+(?<day>\d\d)\/(?<month>\d\d)\/(?<year>\d\d)/', $report, $firstUnbrickDate); // first unbrick date

		preg_match('/Sold by\s*:\s+(?<soldBy>.*?)(\.|\s*<br>)/', $report, $soldBy); //soldBy

		preg_match('/Purchased In\s*:\s+(?<soldIn>.*?)(\.|\s*<br>)/', $report, $soldIn); //soldIn

		if(preg_match('/Model\s*:\s+(?<model>.*?)(\.|\s*<br>)/', $report, $model) && isset($model['model'])) {
			$modelLine = $model['model'];
		} elseif(preg_match('/Config\s*:\s+(?<model>.*?)(\.|\s*<br>)/', $report, $model) && isset($model['model'])) {
			$modelLine = $model['model'];
		}
		if(isset($modelLine)) {
			$this->info("ModelLine: $modelLine");
			if(preg_match('/\s(?<capacity>\d{2,3}.*?)(\.|\s*gb|GB)/', $modelLine, $capacity) && isset($capacity['capacity'])) {
				$capacity = $capacity['capacity'];
			} elseif(preg_match('/(?<capacity>\d{2,3}?)(gb|GB)/', $modelLine, $capacity) && isset($capacity['capacity'])) {
				$capacity = $capacity['capacity'];
			}
			if($capacity) {
				if($capacity != 0) {
					$stock->capacity = $capacity;
					$this->info("Capacity: ".$capacity);
				}

			}
		}

		$this->question("Stock ID: ".$stock->id);

		if ($firstUnbrickDate) {
			$firstUnbrickDate = "20$firstUnbrickDate[year]-$firstUnbrickDate[month]-$firstUnbrickDate[day] 00:00:00";
			$this->info("First Unbrick Date: ".$firstUnbrickDate);
			$stock->first_unbrick_at = $firstUnbrickDate;
		}
		if($locked) {
			$this->info("Locked: $locked");
		}

		$samsungNetwork = $stock->make == 'Samsung' ? true : false;
		$network = ReportParser::getNetwork($report, $samsungNetwork);
		if($network) {
			$this->info("Network: $network");
			$stock->network = $network;
		}

		$colour = ReportParser::getColour($report);
		if($colour) {
			$this->info("Colour: $colour");
			$stock->colour = $colour;
		}

		$serial = ReportParser::getSerialNumber($report);
		if($serial) {
			$this->info("Serial: $serial");
			$stock->serial = $serial;
		}

		if($locked == "Unlocked" || $locked == "FALSE")
			$stock->network = "Unlocked";

		if($soldBy && isset($soldBy['soldBy'])) {
			$soldBy = $soldBy['soldBy'];
			$this->info("Sold By: $soldBy");
			$stock->sold_by = $soldBy;
		}
		if($soldIn && isset($soldIn['soldIn'])) {
			$soldIn = $soldIn['soldIn'];
			$this->info("Sold In: $soldIn");
			$stock->sold_in = $soldIn;
		}

		// Check if iCloud Locked
		if(strpos($report, "Find my iPhone: Enabled") !== false || strpos($report, "FMiP Enabled: TRUE") !== false) {
			$logMessage = "This device has an iCloud account";
			StockLog::create([
				'stock_id' => $stock->id,
				'user_id' => $check->user ? $check->user->id : null,
				'content' => $logMessage
			]);
			$this->question($logMessage);

			if($stock->grade != Stock::GRADE_LOCKED) {
				$stock->grade = Stock::GRADE_LOCKED;
				$this->error('Changing Grade - iCloud Locked');
			}
		} elseif(strpos($report, "Find my iPhone: Disabled") !== false || strpos($report, "FMiP Enabled: FALSE") !== false) {
			$logMessage = "This device is iCloud free as of ".Carbon::now()->format("d/m/Y H:i:s");
			StockLog::create(['stock_id' => $stock->id, 'user_id' => $check->user ? $check->user->id : null, 'content' => $logMessage]);
			$this->question($logMessage);
		}

		$this->info("Network: ".$stock->network);
		$stock->save();
		$this->info("Saved - $stock->id");
	}

	protected function parseUnlockReport($check)
	{
		$this->info("Check $check->id Unlock ".$check->unlock->id);
		$report = $check->response;
		$unlock = $check->unlock;

		$data = "";
		if(preg_match('/Model\s*:\s+(?<model>.*?)(\.|\s*<br>)/', $report, $model) && isset($model['model'])) {
			$data = $model['model'];
			$this->question("Instant Model: $data");
		} elseif(preg_match('/Actual Product\s*:\s+(?<model>.*?)(\.|\s*<br>)/', $report, $model) && isset($model['model'])) {
			$data = $model['model'];
			$this->question("Extended Model: $data");
		}

		$unlock->check_status = GsxCheck::STATUS_DONE;
		$unlock->check_data = $data;
		$unlock->save();
	}

	protected function parseCapacityColourReport($check)
	{
		$stock = $check->stock;
		$changes = '';
		if(preg_match('/Capacity\s*:\s+(?<capacity>.*?)(\.|\s*<br)/', $check->report, $capacity) && isset($capacity['capacity'])) {
			$capacity = $capacity['capacity'];
			if($stock->capacity != $capacity) {
				$stock->capacity = $capacity;
				$changes .= ' Capacity Updated, from '.$stock->getOriginal('capacity')." to $capacity.";
			}
		}

		if(preg_match('/Color\s*:\s+(?<colour>.*?)(\.|\s*<br)/', $check->report, $colour) && isset($colour['colour'])) {
			$colour = $colour['colour'];
			if($stock->colour != $colour) {
				$stock->colour = $colour;
				$changes .= " Colour Updated, from ".$stock->getOriginal('colour')." to $colour.";
			}
		}

		if($changes) {
			$changes = 'Process Capacity/Colour Check:'.$changes;
			$stock->save();

			StockLog::create([
				'stock_id' => $stock->id,
				'content' => $changes
			]);
		}



	}


}
