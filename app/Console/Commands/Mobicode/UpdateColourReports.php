<?php namespace App\Console\Commands\Mobicode;

use App\Models\ImeiReport;
use App\Models\Stock;
use Illuminate\Console\Command;
use Exception;

class UpdateColourReports extends Command
{

	protected $name = 'mobicode:update-colour-reports';

	protected $description = 'Update Stock Items Colour when Unknown or Mixed (based on existing GSX Check Reports).';


	public function handle()
	{
		$items = Stock::with(['imei_report' => function($query) {
			$query->type(ImeiReport::TYPE_NETWORK)->where('status', ImeiReport::STATUS_DONE)->where('mobicode', 1);
		}])
			->whereIn('colour', ['Mixed', 'Unknown'])
			->whereHas('imei_report', function($query) {
				$query->type(ImeiReport::TYPE_NETWORK)->where('status', ImeiReport::STATUS_DONE)->where('mobicode', 1);
			})
			->where('name', 'like', '%iphone%')
			->whereIn('status', [Stock::STATUS_IN_STOCK, Stock::STATUS_BATCH])
			->where('update_colour', "!=",'Error')
			->orderBy('id', 'desc')
			->limit(10)
			->get();

		if(!count($items)) {
			$this->info("Nothing to process");
			return;
		}

		$total = count($items);
		$done=0;
		$colourFound = 0;

		foreach($items as $item) {
			try {
				$report = $item->imei_report;
				$this->info("\nStock ID: $item->id Colour: $item->colour ");
				$this->checkColour($item, $report->report);
				if ($item->colour != "Mixed") {
					$colourFound++;
				}
			} catch(Exception $e) {
				$this->error("Stock ID: $item->id Exception: ".$e->getMessage());
				alert("Update Colour Reports Exception: ".$e);
			}
			$done++;
			progress($done, $total);
		}
		$this->question("Colour Found: $colourFound/$total");
	}

	protected function checkColour($item, $report)
	{
		if(preg_match('/GB\s(?<colour>.*?)(\.|\s*<br>)/', $report, $colour) && isset($colour['colour'])) {
			$colour = $colour['colour'];
		} elseif(preg_match('/GB\,(?<colour>.*?)(\.|\s*<br>)/', $report, $colour) && isset($colour['colour'])) {
			$colour = $colour['colour'];
		}
		if($colour) {
			$test = ucfirst(strtolower($colour));
			$colours = Stock::getColourMapping();
			if(isset($colours[$test])) {
				$colour = $colours[$test];
				$item->colour = $colour;
				$item->save();
				$this->question("Stock ID: $item->id Colour matched: ".$colour);
			} elseif(isset($colours[strtoupper($test)])) {
				$colour = $colours[strtoupper($test)];
				$item->colour = $colour;
				$item->save();
				$this->question("Stock ID: $item->id Colour matched: ".$colour);
			} else {
				$this->comment("Stock ID: $item->id - Not Found ".$colour['colour']);
			}
		} else {
			$this->comment("Stock ID: $item->id - Not Found, Update Colour = Error");
			$item->update_colour = "Error";
			$item->save();
		}
	}
}
