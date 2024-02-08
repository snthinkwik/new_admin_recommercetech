<?php namespace App\Console\Commands\BackMarket;

use App\Models\BackMarketAveragePrice;
use App\Models\BackMarketProduct;
use App\Models\Product;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class AddDynamicPriceForBackMarketForSecondLogic extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'add:second-dynamic-price';

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
        $productList = Product::select(['id','slug', 'product_name', 'category', 'ean', 'model','make','back_market_id'])->whereNotNull('back_market_id')->where('retail_comparison', 1)->orderBy('id', 'ASC')->get();
       // $productList = Product::select(['id','slug', 'product_name', 'category', 'ean', 'model','make','back_market_id'])->where('id','166134569')->whereNotNull('back_market_id')->where('retail_comparison', 1)->orderBy('id', 'ASC')->get();

        $conditionList=['1','2','3'];

        foreach ($productList as $product)
        {

            foreach ($conditionList as $condition){


                $header = array(
                    "Authorization:Basic ".config('services.back_market.token'),
                    'Accept-Language:en-gb'
                );
                $ua = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/525.13 (KHTML, like Gecko) Chrome/0.A.B.C Safari/525.13';

                $curl = curl_init();


                curl_setopt_array($curl, array(

                    CURLOPT_URL => "https://www.backmarket.fr/bm/buybox?aesthetic_grade=".$condition."&merchant_id=3699&product_id=".$product->back_market_id."&special_offer_type=0",
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

                if(!is_string($data[0])){
                    foreach ($data as $item){

                        if($item->country==="GB" && !is_null($item->winnerPrice)){
                            $backMarketAverage= BackMarketAveragePrice::firstOrNew([
                                'back_market_product_id' => $product->back_market_id,
                                'condition'=>$condition,
                            ]);
                            $backMarketAverage->product_id=$product->id;
                            $backMarketAverage->back_market_product_id=$product->back_market_id;
                            $backMarketAverage->condition=$condition;
                            $backMarketAverage->price='';
                            $backMarketAverage->price_for_buybox=$item->winnerPrice;
                            $backMarketAverage->category=$product->category;
                            $backMarketAverage->product_name=$product->product_name;
                            $backMarketAverage->ean=$product->ean;
                            $backMarketAverage->model=$product->model;
                            $backMarketAverage->mpn=$product->slug;
                            $backMarketAverage->make=$product->make;
                            $backMarketAverage->recomme_product_id=$product->id;
                            $backMarketAverage->save();

                        }
                    }
                }


                if(isset($data['error']->code)){
                    //$this->error($data['error']->message);
                    return['status'=>"error",'message'=>$data['error']->message];
                }

            }

        }

	}



}
