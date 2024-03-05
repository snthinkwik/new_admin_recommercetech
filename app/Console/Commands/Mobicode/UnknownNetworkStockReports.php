<?php namespace App\Console\Commands\Mobicode;

use App\Models\Mobicode\GsxCheck;
use App\Models\Stock;
use App\Support\ReportParser;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class UnknownNetworkStockReports extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'mobicode:unknown-network-stock-reports';

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
		$reports = GsxCheck::where('service_id', GsxCheck::SERVICE_LOCKED_UNLOCKED_CHECK)->where('status', GsxCheck::STATUS_DONE)->where('processed', true)->whereHas('stock', function($q){
			$q->where('network', 'unknown');
		})->orderByRaw('RAND()')->limit(250)->get();

		$this->info("Found Reports: ".count($reports));

		foreach($reports as $report) {
			$this->info("$report->id $report->service_id $report->processed $report->imei | ".$report->stock->id." ".$report->stock->network." ".$report->stock->imei);
			$network = ReportParser::getNetwork($report->report, false);
			$this->question($network);
			if($network) {
				$report->processed = false;
				$report->save();
			} else {
				$this->comment($report->report);
			}
		}
	}
}
