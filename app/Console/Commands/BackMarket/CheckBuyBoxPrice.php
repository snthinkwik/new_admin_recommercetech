<?php namespace App\Console\Commands\BackMarket;

use App\BackMarketAveragePrice;
use App\BackMarketEawData;
use App\Product;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class CheckBuyBoxPrice extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'check:buy-box-price';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {

        $backMarketRawData = BackMarketEawData::get();
        foreach ($backMarketRawData as $backMarket) {


            if ($backMarket->condition == 10) {
                $condition = 1;
            } elseif ($backMarket->condition == 11) {
                $condition = 2;
            } elseif ($backMarket->condition == 12) {
                $condition = 3;
            } else {
                $condition = 0;
            }

            $backMarketAverage = BackMarketAveragePrice::firstOrNew([
                'back_market_product_id' => $backMarket->product_id,
                'condition' => $condition,
            ]);

            $header = array(
                "Authorization:Basic " . config('services.back_market.token'),
                'Accept-Language:en-gb'
            );
            $ua = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/525.13 (KHTML, like Gecko) Chrome/0.A.B.C Safari/525.13';

            $curl = curl_init();


            curl_setopt_array($curl, array(

                CURLOPT_URL => "https://www.backmarket.fr/bm/buybox?aesthetic_grade=" . $condition . "&merchant_id=3699&product_id=" . $backMarket->product_id . "&special_offer_type=0",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
                CURLOPT_HTTPHEADER => $header,
                CURLOPT_USERAGENT => $ua
            ));

            $result = curl_exec($curl);


            if (!$result) {
                die("Connection Failure");
            }
            $data = (array)json_decode($result);
            $winnerPrice = '';
            $sameMerchantWinner=false;
            $buyBox=0;
            if (!is_string($data[0])) {
                foreach ($data as $item) {

                    if ($item->country === "GB" && !is_null($item->winnerPrice)) {
                        $winnerPrice = $item->winnerPrice;
                        $sameMerchantWinner=$item->sameMerchantWinner;
                        $buyBox=1;


                    }
                }

                $product = Product::where('back_market_id',$backMarket->product_id)->first();

                if (!is_null($product)) {
                    $backMarketAverage->product_id = $product->id;
                    $backMarketAverage->back_market_product_id = $product->back_market_id;
                    $backMarketAverage->condition = $condition;
                    $backMarketAverage->price = $backMarket->price;
                    $backMarketAverage->price_for_buybox = $winnerPrice;
                    $backMarketAverage->category = $product->category;
                    $backMarketAverage->product_name = $product->product_name;
                    $backMarketAverage->ean = $product->ean;
                    $backMarketAverage->model = $product->model;
                    $backMarketAverage->mpn = $product->slug;
                    $backMarketAverage->make = $product->make;
                    $backMarketAverage->recomme_product_id = $product->id;
                    $backMarketAverage->buy_box = $buyBox;
                    $backMarketAverage->same_merchant_winner=$sameMerchantWinner;
                    $backMarketAverage->save();

                    $this->info("BackMarket Id:-" . $backMarket->product_id);
                }


            }

        }

    }

}