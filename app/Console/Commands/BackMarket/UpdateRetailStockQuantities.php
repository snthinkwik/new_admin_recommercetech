<?php namespace App\Console\Commands\BackMarket;

use App\Models\BackMarketUpdateLog;
use App\Models\Stock;
use Illuminate\Console\Command;
use DB;
use Setting;
use Symfony\Component\Console\Input\InputOption;

class UpdateRetailStockQuantities extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'back-market:update-retail-stock-quantities';

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
		$qtyZero = $this->option('qty-zero');

		if (!Setting::get('crons.back-market-update-retail-stock-quantities.enabled') && !$qtyZero) {
			die("Cron turned off. Exiting\n");
		}

		$retailStock = Stock::where('status', Stock::STATUS_RETAIL_STOCK)->where('new_sku', '!=', '')->select(DB::raw('count(*) as count, new_sku'))->groupBy('new_sku')->get();

		$backMarketUpdateDetails = [
			'cron' => $this->name,
			'sku_qty' => 0,
			'found_qty' => 0,
			'updated_qty' => 0,
			'not_found_qty' => 0,
			'update_error_qty' => 0,
			'details' => [],
		];

		if(!count($retailStock)) {
			$this->info("Nothing to Process");
			$this->saveBackMarketUpdate($backMarketUpdateDetails);
			return;
		}

		$this->info("Found: ".$retailStock->count());

		$backMarket = app('App\Contracts\BackMarket');

		$updated = 0;
		$errors = 0;
		$found = 0;
		$updateErrors = 0;
		$total = $retailStock->count();
		$detailsNotFound = [];
		$detailsUpdated = [];
		$detailsUpdateErrors = [];
		$detailsSameAmount = [];
		$detailsFound = [];

		foreach($retailStock as $retail) {
			$this->info($retail->new_sku . " - " . $retail->count);
			$parameters = ["sku" => $retail->new_sku];
			$res = $backMarket->makeGetRequest('listings/detail', $parameters);
			//$listingId = "";
			if($res->status == "success" && $res->content && isset($res->content->listing_id)) {
				$found++;
				$listingId = $res->content->listing_id;
				$oldQuantity = $res->content->quantity;
				$newQuantity = $qtyZero ? 0 : $retail->count;
				$sku = $res->content->sku;
				$this->question("$sku | Listing ID: $listingId | Old: ".$oldQuantity." | New: $newQuantity");
				$detailsFound[] = $retail->new_sku;
				$updateParameters = [
					'quantity' => (int) $newQuantity
				];
				$url = "listings/$listingId";
				$updateParameters = json_encode($updateParameters);
				$this->comment($updateParameters);
				$this->comment($url);
				try {
					if($oldQuantity != $newQuantity) {
						$backMarketItemUpdate = $backMarket->makePostRequest($url, $updateParameters);
						$this->comment(json_encode($backMarketItemUpdate));
						$this->comment($backMarketItemUpdate->content->quantity." - ".$newQuantity);
						if($backMarketItemUpdate->content->quantity != $newQuantity) {
							$updateErrors++;
							$detailsUpdateErrors[] = $retail->new_sku;
						} else {
							$updated++;
							$detailsUpdated[] = ['sku' => $retail->new_sku, 'old' => $oldQuantity, 'new' => $newQuantity];
						}
					} else {
						// if same quantity, don't send post request
						$updated++;
						$detailsUpdated[] = ['sku' => $retail->new_sku, 'old' => $oldQuantity, 'new' => $newQuantity];
					}

				} catch (\Exception $e) {
					$this->error($e->getMessage());
					$updateErrors++;
					$detailsUpdateErrors[] = $retail->new_sku;
				}

			} elseif($res->status == "success" && isset($res->content->error)) {
				$this->comment("Error: ".$res->content->error->message);
				$errors++;
				$detailsNotFound[] = $retail->new_sku;
			}
		}

		$this->comment("Results:");
		$this->question("Updated: $updated");
		$this->question("Errors: $errors");
		$this->question("Total: $total");
		$this->question("Found: $found");

		$details = [
			'found' => $detailsFound,
			'not_found' => $detailsNotFound,
			'updated' => $detailsUpdated,
			'update_error' => $detailsUpdateErrors,
			'not_updated_same_amount' => $detailsSameAmount
		];

		$backMarketUpdateDetails['updated_qty'] = $updated;
		$backMarketUpdateDetails['found_qty'] = $found;
		$backMarketUpdateDetails['not_found_qty'] = $errors;
		$backMarketUpdateDetails['sku_qty'] = $total;
		$backMarketUpdateDetails['details'] = $details;
		$this->saveBackMarketUpdate($backMarketUpdateDetails);
	}

	protected function saveBackMarketUpdate($details)
	{
		BackMarketUpdateLog::create($details);
	}

	protected function getOptions()
	{
		return [
			['qty-zero', 'qty-zero', InputOption::VALUE_OPTIONAL, 'Set Qty as 0'],
			['run-once', 'run-once', InputOption::VALUE_OPTIONAL, 'Run Once - for running from AdminSettings->Cron']
		];
	}
}
