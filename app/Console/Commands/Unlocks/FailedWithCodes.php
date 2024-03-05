<?php namespace App\Console\Commands\Unlocks;

use App\Commands\Unlocks\Emails\Unlocked;
use App\ImeiReport;
use App\Unlock;
use DB;
use Queue;
use Illuminate\Console\Command;

class FailedWithCodes extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'unlocks:failed-with-codes';

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
		$unlocks = Unlock::where('status', Unlock::STATUS_FAILED)->whereHas('imei_report', function($q){
			$q->where('status', ImeiReport::STATUS_DONE);
			$q->where('report_status', 'Completed');
			$q->where(function($r){
				$r->where('report', 'like', "%NETWORK%");
				$r->orWhere('report', 'like', "%Network%");
				$r->orWhere(function($rq){
					$rq->where('report', 'regexp', "[0-9]{8}");
					$rq->where(DB::raw('LENGTH(`report`)'), 8);
				});
			});
		})->limit(25)->get();

		if(!count($unlocks)) {
			$this->info('Nothing to Process');
			return;
		}

		$this->info("Unlocks Found: ".$unlocks->count());

		foreach($unlocks as $unlock) {
			$this->question("$unlock->id $unlock->imei $unlock->status");
			$this->comment($unlock->status_description);
			$this->info($unlock->imei_report->report);

			if($unlock->stock_item){
				$item = $unlock->stock_item;
				$item->network = "Unlocked";
				$item->save();
			}
			$unlock->status = Unlock::STATUS_UNLOCKED;
			$unlock->status_description = $unlock->imei_report->report;
			$unlock->save();
			Queue::pushOn('emails', new Unlocked($unlock, true));
		}
	}

}
