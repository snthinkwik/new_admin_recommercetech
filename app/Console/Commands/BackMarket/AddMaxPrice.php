<?php namespace App\Console\Commands\BackMarket;

use App\Models\BackMarketAveragePrice;
use App\Models\BackMarketEawData;
use App\Models\BackMarketMaxPrice;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class AddMaxPrice extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'back-market:add-max-price';



	public function handle()
	{


        $header = array(
            "Authorization:Basic ".config('services.back_market.token'),
            'Accept-Language:en-gb'
        );

        $i = 1;

        do {
            $backMarketApiResponse = getMaxPrice($header, $i);


            if(isset($backMarketApiResponse['status'])){
                $this->error($backMarketApiResponse['message']);
                return false;
            }


            $j = ceil((isset($backMarketApiResponse["count"]) ? $backMarketApiResponse["count"] : 0) / (10));




            if (isset($backMarketApiResponse["results"])) {
                foreach ($backMarketApiResponse["results"] as $items){
                           $backMarketMaxPrice= BackMarketMaxPrice::firstOrNew([
                               'back_market_product_id' => $items->backmarket_id,
                           ]);

                    $backMarketMaxPrice->back_market_product_id=$items->backmarket_id;
                    $backMarketMaxPrice->max_price=$items->max_price;
                    $backMarketMaxPrice->min_price=$items->min_price;
                    $backMarketMaxPrice->save();
                    $this->info("Back Market Product Id:-".$backMarketMaxPrice->back_market_product_id);
                    $this->info("Back Market Max Price:-".$backMarketMaxPrice->max_price);


                }



            }

            $i++;
        } while ($j >= $i);

        $this->info("mxa price successfully Added");
    }





}
