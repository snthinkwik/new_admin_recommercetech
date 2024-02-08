<?php namespace App\Console\Commands\BackMarket;

use App\BackMarketEawData;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class AddBackMarketRawData extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'back-market:raw-data';

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
    public function handle()
    {

        $header = array(
            "Authorization:Basic ".config('services.back_market.token'),
            'Accept-Language:en-gb'
        );

        $i = 1;

        do {
            $backMarketApiResponse = getBuyBoxData($header, $i);

            if(isset($backMarketApiResponse['status'])){
                if($backMarketApiResponse['status'] ==="error"){
                    $this->error($backMarketApiResponse['message']);
                    return false;
                }

            }


            $j = ceil((isset($backMarketApiResponse["count"]) ? $backMarketApiResponse["count"] : 0) / (10));



            if (isset($backMarketApiResponse["results"])) {

                foreach ($backMarketApiResponse["results"] as $backMarket) {
                    $backmarketAverage= BackMarketEawData::firstOrNew([
                        'product_id' => $backMarket->product,
                        'condition'=>$backMarket->condition,
                    ]);


                    $ua = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/525.13 (KHTML, like Gecko) Chrome/0.A.B.C Safari/525.13';

                    $curl = curl_init();

                    curl_setopt_array($curl, array(

                        CURLOPT_URL => "https://www.backmarket.fr/ws/products/".$backMarket->product,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
                        CURLOPT_HTTPHEADER => $header,
                        CURLOPT_USERAGENT=>$ua
                    ));

                    $result = curl_exec($curl);


                    if (!$result) {
                        die("Connection Failure");
                    }
                    $data = (array) json_decode($result);

                    if(isset($data['error']->code)){
                        //$this->error($data['error']->message);
                        return['status'=>"error",'message'=>$data['error']->message];
                    }

                    $backmarketAverage->product_id=$backMarket->product;
                    $backmarketAverage->sku=$backMarket->sku;
                    $backmarketAverage->quantity=$backMarket->quantity;
                    $backmarketAverage->price=$backMarket->price;
                    $backmarketAverage->buybox=$backMarket->buybox;
                    $backmarketAverage->price_for_buybox=$backMarket->price_for_buybox;
                    $backmarketAverage->condition=$backMarket->condition;
                    $backmarketAverage->same_merchant_winner=$backMarket->same_merchant_winner;
                    $backmarketAverage->ean=$data['ean'];
                    $backmarketAverage->save();

                }

            }

            $i++;
        } while ($j >= $i);
    }


}
