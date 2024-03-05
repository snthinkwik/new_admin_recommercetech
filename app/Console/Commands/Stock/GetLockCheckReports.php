<?php namespace App\Console\Commands\Stock;

use App\LockCheck;
use App\Support\ReportParser;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class GetLockCheckReports extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'stock:get-lock-check-reports';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Checks if reports are available for processing lock checks.';

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
		$checks = LockCheck::where('status', LockCheck::STATUS_PROCESSING)->limit(100)->orderByRaw('RAND()')->get();

		if(!count($checks)) {
			$this->info("Nothing to Process");
			return;
		}

		$this->info("Get Lock Check Reports: ".$checks->count());

		foreach($checks as $check) {
			$this->question("Lock Check #$check->id");

			$response = $this->click2unlock->getReport($check->external_id);
			$this->comment(json_encode($response));
			if(!is_object($response)) {
				$this->error("not an object");
				continue;
			}
			if($response->status == "success" && $response->data->report_status == "success") {
				$check->response = json_encode($response);
				$check->report = $response->data->report;
				$check->status = LockCheck::STATUS_DONE;
				$check->result = LockCheck::parseReport($check->report);
				$this->info("Check Updated");
				$check->save();
				$stock = $check->stock;
				if($check->result == "Unlocked") {
					$stock->network = "Unlocked";
					$stock->save();
					$this->question("Network Updated");
				}
				$colour = ReportParser::getColour($check->report);
				if($colour) {
					$stock->colour = $colour;
					$stock->save();
					$this->info("Colour Found: $colour");
				}

				$serial = ReportParser::getSerialNumber($check->report);
				if($serial) {
					$stock->serial = $serial;
					$stock->save();
					$this->info("Serial Number Found: $serial");
				}
				$this->info("Result: $check->result");

			}
		}
	}

}
