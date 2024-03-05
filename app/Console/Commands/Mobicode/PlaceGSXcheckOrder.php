<?php namespace App\Console\Commands\Mobicode;

use App\Models\Mobicode\GsxCheck;
use App\Models\StockLog;
use Illuminate\Console\Command;
use Exception;
use App\Contracts\Click2Unlock;
use Carbon\Carbon;

class PlaceGSXcheckOrder extends Command {

	protected $name = 'mobicode:place-gsx-check-order';

	protected $description = 'Command description.';

	public function __construct()
	{
		$this->click2unlock = app('App\Contracts\Click2Unlock');
		parent::__construct();
	}

	public function handle()
	{
		$checks = GsxCheck::where('status', GsxCheck::STATUS_NEW)->where(function($query) {
			$query->where(function($q){
				$q->whereNotNull('stock_id');
			});
			$query->orWhere(function($q){
				$q->whereNotNull('unlock_id');
			});
			$query->orWhere(function($q){
				$q->whereNotNull('stock_id')->whereNotNull('sale_id');
			});
			$query->orWhere(function($q){
				$q->whereNotNull('stock_id')->whereNotNull('service_id');
			});
		})->limit(25)->orderByRaw('RAND()')->get();

		$this->info("GsxCheck Place Order: ".count($checks)." checks");

		if(!count($checks)) {
			$this->info("Nothing to Process");
			return;
		}

		$done=0;
		$total = count($checks);


		foreach($checks as $check) {
			try {
				if($check->stock && $check->stock->make == 'Samsung')
					$response = $this->click2unlock->unlock($check->imei, 90); // samsung check
				elseif($check->stock && $check->service_id == GsxCheck::SERVICE_CAPACITY_COLOUR_CHECK)
					$response = $this->click2unlock->unlock($check->imei, GsxCheck::SERVICE_CAPACITY_COLOUR_CHECK); // capacity colour check
				elseif($check->stock && $check->service_id == GsxCheck::SERVICE_ICLOUD_STATUS_CHECK)
					$response = $this->click2unlock->unlock($check->imei, GsxCheck::SERVICE_ICLOUD_STATUS_CHECK); // icloud status check
				else
					$response = $this->click2unlock->gsxCheck($check->imei);

				$this->info($check->id);
				$this->question(json_encode($response));
				if($response->status == "success") {
					$check->status = GsxCheck::STATUS_PROCESSING;
					$check->external_id = $response->data->check_id;
					$check->save();
					if($check->stock_id && $check->user_id) {
						StockLog::create([
							'user_id' => $check->user ? $check->user->id : null,
							'stock_id' => $check->stock->id,
							'content' => "GSX Check Order Submitted: " . json_encode($response),
						]);
					}
					elseif($check->stock_id && $check->service_id == GsxCheck::SERVICE_CAPACITY_COLOUR_CHECK) {
						StockLog::create([
							'user_id' => null,
							'stock_id' => $check->stock->id,
							'content' => "iPad Capacity/Colour Check Order Submitted: " . json_encode($response),
						]);
					}
					if($check->unlock_id) {
						$unlock = $check->unlock;
						$unlock->check_status = GsxCheck::STATUS_PROCESSING;
						$unlock->save();
					}
				} else {
					$this->comment(json_encode($response));
					continue;
					// usually it's balance issue
					$check->status = GsxCheck::STATUS_ERROR;
					$check->response = json_encode($response);
					$check->save();
					if(($check->stock_id && $check->user_id) || ($check->stock_id && $check->service_id == GsxCheck::SERVICE_CAPACITY_COLOUR_CHECK)) {
						StockLog::create([
							'user_id' => $check->user ? $check->user->id : null,
							'stock_id' => $check->stock->id,
							'content' => "GSX Check Report Error: " . json_encode($response) . " \n(Process GSX Check Report Cron)",
						]);
					}
					if($check->unlock_id) {
						$unlock = $check->unlock;
						$unlock->check_status = GsxCheck::STATUS_ERROR;
						$unlock->save();
					}
				}
			} catch(Exception $e) {
				continue;
				$check->status = GsxCheck::STATUS_ERROR;
				$check->response = $e->getMessage();
				$check->save();
				if($check->stock_id && $check->user_id) {
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
		}
	}


}
