<?php namespace App\Console\Commands\Orderhub;

use App\Models\ChannelGrabberUpdateLog;
use App\Models\Stock;
use Illuminate\Console\Command;
use DB;

class UpdateRetailStockQuantities extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'orderhub:update-retail-stock-quantities';

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
		$retailStock = Stock::where('status', Stock::STATUS_RETAIL_STOCK)->where('new_sku', '!=', '')->select(DB::raw('count(*) as count, new_sku'))->groupBy('new_sku')->get();

		$channelGrabberUpdateDetails = [
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
			$this->saveChannelGrabberUpdate($channelGrabberUpdateDetails);
			return;
		}

		$this->info("Found: ".$retailStock->count());

		$orderhub = app('App\Contracts\Orderhub');

		$accessToken = $orderhub->getAccessToken();

		$this->comment($accessToken);
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
			$this->info($retail->new_sku." - ".$retail->count);
			$parameters = ["sku[]" => $retail->new_sku];
			$attempts = 0;
			do {
				$attempts++;
				sleep(2);
				$orderhubItem = $orderhub->makeGetRequest($accessToken, "stock", $parameters);
				if (is_object($orderhubItem) || $attempts > 5) {
					break;
				}
			} while (true);

			if(is_object($orderhubItem) && isset($orderhubItem->_embedded->stock) && isset($orderhubItem->_embedded->stock[0])) {
				$found++;
				$detailsFound[] = $retail->new_sku;
				$id = $orderhubItem->_embedded->stock[0]->id;
				$etag = $orderhubItem->etags->{$id};
				$oldAvailableForSale = $orderhubItem->_embedded->stock[0]->availableForSale;
				$newAvailableForSale = $retail->count;
				$sku = $orderhubItem->_embedded->stock[0]->sku;
				$this->info("ID: $id | etag: $etag | Old: $oldAvailableForSale | New: $newAvailableForSale | sku: $sku");
				/*if($oldAvailableForSale == $newAvailableForSale) {
					$sameAmount++;
					$detailsSameAmount[] = $retail->new_sku;
					$this->question("Same amount, skip");
					continue;
				}*/

				$updateParameters = [
					'id' => (int) $id,
					'sku' => $sku,
					'availableForSale' => (int) $newAvailableForSale
				];
				$updateParameters = json_encode($updateParameters);
				$url = "stock/$id";
				$this->info($updateParameters);
				$this->info($url);
				try {
					$orderhubItemUpdate = $orderhub->makePutRequest($accessToken, $url, $updateParameters, $etag);
					$this->comment(json_encode($orderhubItemUpdate));
					$updated++;
					$detailsUpdated[] = ['sku' => $retail->new_sku, 'old' => $oldAvailableForSale, 'new' => $newAvailableForSale];
				} catch (\Exception $e) {
					$updateErrors++;
					$detailsUpdateErrors[] = $retail->new_sku;
				}

			} else {
				$this->error(json_encode($orderhubItem));
				$errors++;
				$detailsNotFound[] = $retail->new_sku;
			}
		}

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

		$channelGrabberUpdateDetails['updated_qty'] = $updated;
		$channelGrabberUpdateDetails['found_qty'] = $found;
		$channelGrabberUpdateDetails['not_found_qty'] = $errors;
		$channelGrabberUpdateDetails['sku_qty'] = $total;
		$channelGrabberUpdateDetails['details'] = $details;
		$this->saveChannelGrabberUpdate($channelGrabberUpdateDetails);
	}

	protected function saveChannelGrabberUpdate($details)
	{
		ChannelGrabberUpdateLog::create($details);
	}
}
