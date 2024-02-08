<?php namespace App\Console\Commands\Mobicode;

use App\Mobicode\GsxCheck;
use App\StockLog;
use Illuminate\Console\Command;
use App\Contracts\Click2Unlock;
use Carbon\Carbon;
use Exception;

class GetGSXcheckReports extends Command {

	protected $name = 'mobicode:get-gsx-check-reports';

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
		$checks = GsxCheck::where('status', GsxCheck::STATUS_PROCESSING)->where(function($query) {
			$query->where(function($q){
				$q->whereNotNull('stock_id');
			});
			$query->orWhere(function($q){
				$q->whereNotNull('unlock_id');
			});
			$query->orWhere(function($q){
				$q->whereNotNull('sale_id');
			});
			$query->orWhere(function($q){
				$q->whereNotNull('stock_id')->where('service_id', GsxCheck::SERVICE_CAPACITY_COLOUR_CHECK);
			});
		})->limit(50)->orderByRaw('RAND()')->get();

		$this->info("GsxCheck Get Reports: ".count($checks)." checks");

		if(!count($checks)) {
			$this->info("Nothing to Process");
			return;
		}

		$done=0;
		$total = count($checks);

		foreach($checks as $check) {
			try {
				$response = $this->click2unlock->getReport($check->external_id);
				$this->question("GSX Check $check->id External ID $check->external_id");
				if(!is_object($response)) {
					$this->error('not an object');
					continue;
				}
				$this->info($response->status." - ".$response->data->report_status);
				if($response->status == "success" && in_array($response->data->report_status, ["success", "failed", "cancelled"])) {
					$report = str_replace("<b>", "",str_replace('<br>\n',"<br>",str_replace("<\/b>","",json_encode($response))));
					$check->status = $response->data->report_status == "success" ? GsxCheck::STATUS_DONE :  GsxCheck::STATUS_FAILED;
					$check->response = $report;
					if(isset($response->data->report)) {
						$check->report = $response->data->report;
						$report = str_replace("<b>", "",str_replace('<br>\n',"<br>",str_replace("<\/b>","",$response->data->report)));
					}
					$check->save();
					if($check->stock_id && $check->service_id == GsxCheck::SERVICE_CAPACITY_COLOUR_CHECK) {
						StockLog::create([
							'user_id' => null,
							'stock_id' => $check->stock->id,
							'content' => "Capacity/Colour Check Report: Status: ".$response->data->report_status.". Report: " . $report,
						]);
					}
					elseif($check->stock_id && $check->service_id == GsxCheck::SERVICE_ICLOUD_STATUS_CHECK) {
						StockLog::create([
							'user_id' => null,
							'stock_id' => $check->stock->id,
							'content' => "iCloud Status Check Report: Status: ".$response->data->report_status.". Report: " . $report,
						]);
					}
					elseif($check->stock_id) {
						StockLog::create([
							'user_id' => $check->user ? $check->user->id : null,
							'stock_id' => $check->stock->id,
							'content' => "GSX Check Report: Status: " .$response->data->report_status.". Report: " . $report,
						]);
					}
				}

			} catch(Exception $e) {
				alert("Process GSX Check Exception: ".$e);
				$this->question("Check ".$check->id." Exception");
				if(strpos($e->getMessage(), '500 Internal Server Error') !== false) {
					continue; // so it will try to get that report again
				}
				$check->status = GsxCheck::STATUS_ERROR;
				$check->response = $e->getMessage();
				$check->save();
				if($check->stock_id) {
					StockLog::create([
						'user_id' => $check->user ? $check->user->id : null,
						'stock_id' => $check->stock->id,
						'content' => "GSX Check Report Error (Get GSX Check Reports Cron)",
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

}
