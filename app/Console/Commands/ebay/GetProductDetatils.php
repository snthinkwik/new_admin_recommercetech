<?php namespace App\Console\Commands\ebay;

use App\Models\AccessToken;
use App\Models\EbayImage;
use App\Models\EbayOrderItems;
use App\Models\EbayOrders;
use App\Models\Stock;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use GuzzleHttp\Client;

class GetProductDetatils extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'ebay:get-product-detatils';

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

       $ebayItems= EbayOrderItems::select('ebay_order_items.item_number')->where('platform',Stock::PLATFROM_EBAY)
            ->join('master_ebay_orders', 'master_ebay_orders.id', '=', 'ebay_order_items.order_id')
            ->groupBy('ebay_order_items.item_number')
            ->get();


        $accessToken=AccessToken::where('platform','ebay')->first();
        $currentTime = Carbon::now();
        $addTime=\Carbon\Carbon::parse($accessToken->updated_at)->addSecond($accessToken->expires_in);
        $BasicHeaders=ebayBasicToken(config('services.ebay.client_id'),config('services.ebay.client_secret'));

        $this->comment("Initialization....");

        if($currentTime->gt($addTime)){

            $this->comment("Access Token is Expired....");
            $this->comment("New Generated Access Token....");
            $newAccessToken= getEbayRefreshTokenBaseToken($BasicHeaders,$accessToken->refresh_token);
            $accessToken->access_token=$newAccessToken['access_token'];
            $accessToken->expires_in=$newAccessToken['expires_in'];
            $accessToken->save();
            sleep(1);

        }







        foreach ($ebayItems as $items){
            $eanList=[];
            $mpnsList=[];
            $this->info($items->item_number);

           $client = new Client();
        //   $client->setDefaultOption('headers', array('Authorization' => "Bearer {$accessToken->access_token}"));


           try{


               $findEbayProduct=EbayImage::where('items_id',$items->item_number)->first();

               if(is_null($findEbayProduct)){
                   $response = $client->get("https://api.ebay.com/buy/browse/v1/item/v1|".$items->item_number."|0?fieldgroups=PRODUCT");
                   $data = $response->json();

                   if(isset($data['product']['gtins'])){
                       foreach ($data['product']['gtins'] as $gtins){
                           array_push($eanList,$gtins);
                       }
                   }

                   if(isset($data['product']['mpns'])){
                       foreach ($data['product']['mpns'] as $mpns){
                           array_push($mpnsList,substr($mpns, 0, 5));
                       }
                   }



                   if(count($data)>0){
                       $ebayOrderImage = \App\EbayImage::firstOrNew([
                           'items_id' => $items->item_number
                       ]);

                       $ebayOrderImage->items_id=$items->item_number;
                       $ebayOrderImage->image_path=count($data['image'])>0 ?$data['image']['imageUrl']:null;
                       $ebayOrderImage->epid=isset($data['epid'])?$data['epid']:null;
                       $ebayOrderImage->ean=count($eanList)>0?json_encode($eanList):null;
                       $ebayOrderImage->mpn=count($mpnsList)>0?json_encode($mpnsList):null;
                       $ebayOrderImage->save();
                       $this->comment("Image Path Added :-" .$ebayOrderImage->image_path);
                       $this->comment("EAN Added:-" .$ebayOrderImage->ean);
                       $this->comment("MPN Added :-" .$ebayOrderImage->mpn);
                   }
               }else{
                   $this->info($items->item_number." Already Added");
               }



           }catch (\Exception $e){

              // $this->info($e->getMessage());
               continue;
           }

       }



	}



}
