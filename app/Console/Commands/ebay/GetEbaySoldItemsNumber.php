<?php namespace App\Console\Commands\ebay;

use App\AccessToken;
use App\AveragePrice;
use App\EbayOrderSoldDetails;
use App\EbayProductDetails;
use App\EBaySeller;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use GuzzleHttp\Client;
use Carbon\Carbon;


class GetEbaySoldItemsNumber extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'ebay:sold-items';

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
		$averagePrice=AveragePrice::get();

        $sellerList=EBaySeller::all();

        $sellerUserNameList=[];
        foreach ($sellerList as $seller){
            array_push($sellerUserNameList,$seller->user_name);
        }


        $authorization = base64_encode(config('services.ebay2.client_id').':'.config('services.ebay2.client_secret'));
        $header = array("Authorization: Basic {$authorization}", "Content-Type: application/x-www-form-urlencoded");


        $accessToken = AccessToken::where('platform', 'ebay-second')->first();


        $currentTime = Carbon::now();
        $addTime = \Carbon\Carbon::parse($accessToken->updated_at)->addSecond($accessToken->expires_in);




        foreach ($averagePrice as $items){


            $userName=[];
            if(count($items)){
             //   dd($items->mpn);
                $ebayProducts=EbayProductDetails::where('mpn',$items->mpn)->where('condition',$items->condition)->get();

                $client = new Client();


                $client->setDefaultOption('headers', array('Authorization' => "Bearer {$accessToken->access_token},'X-EBAY-C-MARKETPLACE-ID'=>EBAY_GB"));
                $soldPrice=0;
                try {
                    foreach ($ebayProducts as $product){
                        if(in_array($product->user_name,$sellerUserNameList)){

                            if ($currentTime->gt($addTime)) {

                                $this->comment("Access Token is Expired....");
                                $this->comment("New Generated Access Token....");
                                $newAccessToken = getEbayRefreshTokenBaseToken($header, $accessToken->refresh_token);
                                $accessToken->access_token = $newAccessToken['access_token'];
                                $accessToken->expires_in = $newAccessToken['expires_in'];
                                $accessToken->save();
                                sleep(5);

                            }


                            $productGroupResponse = $client->get($product->product_link);
                            $productGroupData = $productGroupResponse->json();



                            if (strpos($product->product_link, 'item_group_id') !== false) {
                                foreach ($productGroupData['items'] as $group){

                                    $userName[$group['seller']['username']][]=$group['estimatedAvailabilities'][0]['estimatedSoldQuantity'];
                                    // array_push($userName,$group['seller']['username']."-".$group['estimatedAvailabilities'][0]['estimatedSoldQuantity']);
                                    $soldPrice+=$group['estimatedAvailabilities'][0]['estimatedSoldQuantity'];
                                }
                            }else{

                                $userName[$productGroupData['seller']['username']][]=$productGroupData['estimatedAvailabilities'][0]['estimatedSoldQuantity'];
                                // array_push($userName,$productGroupData['seller']['username'].'-'.$productGroupData['estimatedAvailabilities'][0]['estimatedSoldQuantity']);
                                $soldPrice+=$productGroupData['estimatedAvailabilities'][0]['estimatedSoldQuantity'];
                            }

                        }


                    }

                    if(count($ebayProducts)){
                        $ebayOrderSold= new EbayOrderSoldDetails();
                        $ebayOrderSold->average_price_id=$items->id;
                        $ebayOrderSold->mpn=$items->mpn;
                        $ebayOrderSold->condition=$items->condition;
                        $ebayOrderSold->user_name=json_encode($userName);
                        $ebayOrderSold->sold_no=$soldPrice;
                        $ebayOrderSold->save();
                    }


                    $this->info("Added New Sold Item Records For This Average Price Id:-".$items->id);
                }catch (\Exception $e){
                    $this->info($e->getMessage());
                    continue;
                }






            }



        }


	}



}
