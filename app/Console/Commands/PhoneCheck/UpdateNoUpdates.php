<?php namespace App\Console\Commands\PhoneCheck;

use App\Models\PhoneCheck;
use App\Models\StockLog;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class UpdateNoUpdates extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'phone-check:update-no-updates';

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
	public function handle()
	{
		$updated = 0;
		$processed = 0;
		PhoneCheck::whereNotNull('stock_id')->orderBy('id', 'desc')->chunk(250, function($phoneChecks) use(&$updated, &$processed) {
			foreach($phoneChecks as $phoneCheck) {
				$this->info("$phoneCheck->id | $phoneCheck->stock_id | $phoneCheck->no_updates");
				$logsCount = StockLog::where('stock_id', $phoneCheck->stock_id)->where('content', 'like', "Changes%UpdatedTimeStamp%")->count();
				$this->question($logsCount." (".($logsCount+1).")");
				if($logsCount+1 != $phoneCheck->no_updates) {
					$phoneCheck->no_updates = $logsCount+1; // +1 as when it's saved for the first time, it's not logged this way
					$phoneCheck->save();
					$updated++;
				}
				$processed++;
			}
		});

		$this->info("Updated: $updated");
		$this->info("Processed: $processed");
	}

}
