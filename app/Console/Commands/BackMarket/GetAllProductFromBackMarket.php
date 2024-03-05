<?php namespace App\Console\Commands\BackMarket;

use App\Models\BackMarketProduct;
use App\Models\CsvFileProgress;
use App\Models\EbayOrderLog;
use App\Models\EbayOrders;
use App\Models\EbayProductDetails;
use App\Models\HistoryLog;
use App\Models\Stock;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class GetAllProductFromBackMarket extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'back-market:all-product';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Command description.';


	public function handle()
    {

        $header = array(
            "Authorization:Basic ".config('services.back_market.token')
        );

        $i = 23077;
        $d = 0;


        $csvFileProgress=new CsvFileProgress;

        $csvFileProgress->status='In Progress';
        $csvFileProgress->description='Staring Add Data In Database';
        $csvFileProgress->save();
        do {

            try {
                $eBayApiResponse = getAllProductsFromBackMarket($header, $i);
                $total=isset($eBayApiResponse["count"])?$eBayApiResponse["count"]:0;
                $this->info($total);
                $j = ceil((isset($eBayApiResponse["count"]) ? $eBayApiResponse["count"] : 0) / (10));

                if (isset($eBayApiResponse["results"])) {
                    foreach ($eBayApiResponse["results"] as $backMarket) {


                        $backMarketProduct = BackMarketProduct::firstOrNew([
                            'back_market_id' => $backMarket->product_id,
                        ]);

                        $backMarketProduct->back_market_id=$backMarket->product_id;
                        $backMarketProduct->title=$backMarket->title;
                        $backMarketProduct->ean=$backMarket->ean;
                        $backMarketProduct->state=$backMarket->state;
                        $backMarketProduct->brand=$backMarket->brand;
                        $backMarketProduct->category_name=$backMarket->category_name;
                        $backMarketProduct->weight=$backMarket->weight;
                        $backMarketProduct->height=$backMarket->height;
                        $backMarketProduct->depth=$backMarket->depth;
                        $backMarketProduct->width=$backMarket->width;
                        $backMarketProduct->save();

                    }
                }

                $i++;
                $this->info($i)   ;
            }catch (\Exception $e){
                $this->info($e->getMessage());
                continue;
            }

           ;


//            $csvFileProgressUpdate=CsvFileProgress::find($csvFileProgress->id);
//            $csvFileProgressUpdate->status='In Progress';
//            $csvFileProgressUpdate->total_added= $total."/".$i*10;
//            $csvFileProgressUpdate->save();

        } while ($j >= $i);
        $csvFileProgressUpdate=CsvFileProgress::find($csvFileProgress->id);
        $csvFileProgressUpdate->file_path="Test";
        $csvFileProgressUpdate->status='Completed';
        $csvFileProgressUpdate->save();




}



}
