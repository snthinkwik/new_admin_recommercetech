<?php namespace App\Console\Commands\ebay;

use App\Models\AccessToken;
use App\Models\AveragePrice;
use App\Models\Category;
use App\Models\EbayNetwork;
use App\Models\EbayProductDetails;
use App\Models\EbayProductSearchPriorities;
use App\Models\EBaySeller;
use App\Models\Product;
use App\Models\Stock;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use GuzzleHttp\Client;

class AddDynamicPriceForTabletAndComputer extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'ebay:tablet-dynamic-price';


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

        $productList = Product::select(['id','slug', 'product_name', 'category', 'ean', 'model', 'make'])->whereIn('category', ['Tablet'])->where('retail_comparison', 1)->where('slug', '!=', '')->orderBy('id', 'ASC')->get();
        $sellerList = EBaySeller::all();


        $sellerUserNameList = [];
        foreach ($sellerList as $seller) {
            array_push($sellerUserNameList, $seller->user_name);
        }


        $BasicHeaders = ebayBasicToken(config('services.ebay2.client_id'), config('services.ebay2.client_secret'));

        $accessToken = AccessToken::where('platform', 'ebay-second')->first();



        $client = new Client();
        $headers = [
            'Authorization' => "Bearer {$accessToken->access_token}",
            'X-EBAY-C-MARKETPLACE-ID'=>"EBAY_GB"
        ];

        $finalProductData = [];
        $i=0;

        foreach ($productList as $product) {
            $productData = [];
            $Category = Category::where('name', $product->category)->whereNotNull('eBay_category_id')->first();

            if (strpos($product->ean, ',') !== false) {
                $eanEx = explode(',', $product->ean);

                foreach ($eanEx as $ean) {


                    try{

                        $ebayProductPriorities=EbayProductSearchPriorities::where('product_id',$product->id)->where('product_id',$product->id)->first();
                        if(is_null($ebayProductPriorities)){

                            $response = $client->get("https://api.ebay.com/buy/browse/v1/item_summary/search?q=" . strtolower($ean) . "&category_ids=" . $Category->eBay_category_id . "&filter=deliveryCountry:GB,itemLocationCountry:GB,buyingOptions:{FIXED_PRICE},price:[20],priceCurrency:GBP&sort=price", ['headers' => $headers]);
                            $data = $response->json();

                            if ($data['total'] > 0) {
                                $productData[$product->category] =$ean.'@'.$product->slug.'@'.$product->ean.'@'.$product->product_name.'@'. $product->model.'@'.$product->make.'@'.$product->id;

                                $addEbayProductPri= new EbayProductSearchPriorities();
                                $addEbayProductPri->product_id=$product->id;
                                $addEbayProductPri->priorities=$ean.'@'.$product->slug.'@'.$product->ean.'@'.$product->product_name.'@'. $product->model.'@'.$product->make.'@'.$product->id;
                                $addEbayProductPri->save();
                            }

                        }else{
                            $productData[$product->category] =$ebayProductPriorities->priorities;
                        }

                    }catch (\Exception $e){

                        $this->info($e->getMessage());
                        continue;

                    }


                }
            } else if (strpos($product->ean, ' ') !== false) {
                $eanEx = explode(' ', trim($product->ean));
                foreach ($eanEx as $ean) {
                    try{

                        $ebayProductPriorities=EbayProductSearchPriorities::where('product_id',$product->id)->first();

                        if(is_null($ebayProductPriorities)){

                            $response = $client->get("https://api.ebay.com/buy/browse/v1/item_summary/search?q=" . strtolower($ean) . "&category_ids=" . $Category->eBay_category_id . "&filter=deliveryCountry:GB,itemLocationCountry:GB,buyingOptions:{FIXED_PRICE},price:[20],priceCurrency:GBP&sort=price",
                                ['headers' => $headers]);
                            $data = $response->json();
                            if ($data['total'] > 0) {
                                // array_push($productData,$ean);
                                //  $this->info($j++);
                                $productData[$product->category] = $ean.'@'.$product->slug.'@'.$product->ean.'@'.$product->product_name.'@'. $product->model.'@'.$product->make.'@'.$product->id;

                                $addEbayProductPri= new EbayProductSearchPriorities();
                                $addEbayProductPri->product_id=$product->id;
                                $addEbayProductPri->priorities=$ean.'@'.$product->slug.'@'.$product->ean.'@'.$product->product_name.'@'. $product->model.'@'.$product->make.'@'.$product->id;
                                $addEbayProductPri->save();
                            }


                        }else{

                            $productData[$product->category] =$ebayProductPriorities->priorities;

                        }




                    }catch (\Exception $e){
                        $this->info($e->getMessage());
                        continue;

                    }
                }
            } else {
                if ($product->ean !== "") {

                    try {


                        $ebayProductPriorities=EbayProductSearchPriorities::where('product_id',$product->id)->first();
                        if(is_null($ebayProductPriorities)){
                            //  $this->info($j++);
                            $response = $client->get("https://api.ebay.com/buy/browse/v1/item_summary/search?q=" . strtolower($product->ean) . "&category_ids=" . $Category->eBay_category_id . "&filter=deliveryCountry:GB,itemLocationCountry:GB,buyingOptions:{FIXED_PRICE},price:[20],priceCurrency:GBP&sort=price",
                                ['headers' => $headers]);
                            $data = $response->json();

                            if ($data['total'] > 0) {
                                // array_push($productData,$product->ean);
                                $productData[$product->category] = $product->ean.'@'.$product->slug.'@'.$product->ean.'@'.$product->product_name.'@'. $product->model.'@'.$product->make.'@'.$product->id;

                                $addEbayProductPri= new EbayProductSearchPriorities();
                                $addEbayProductPri->product_id=$product->id;
                                $addEbayProductPri->priorities=$product->ean.'@'.$product->slug.'@'.$product->ean.'@'.$product->product_name.'@'. $product->model.'@'.$product->make.'@'.$product->id;
                                $addEbayProductPri->save();
                            }
                        }else{
                            $productData[$product->category] =$ebayProductPriorities->priorities;
                        }

                    }catch (\Exception $e){


                        $this->info($e->getMessage());
                        continue;

                    }
                }
            }



            if (!count($productData)) {

                if (strpos($product->slug, ',') !== false) {

                    $eanEx = explode(',', $product->slug);


                    foreach ($eanEx as $slug) {


                        try {

                            $ebayProductPriorities=EbayProductSearchPriorities::where('product_id',$product->id)->first();
                            if(is_null($ebayProductPriorities)){
                                //  $this->info($j++);
                                $response = $client->get("https://api.ebay.com/buy/browse/v1/item_summary/search?q=" . strtolower($slug) . "&category_ids=" . $Category->eBay_category_id . "&filter=deliveryCountry:GB,itemLocationCountry:GB,buyingOptions:{FIXED_PRICE},price:[20],priceCurrency:GBP&sort=price",
                                    ['headers' => $headers]);
                                $data = $response->json();
                                if ($data['total'] > 0) {
                                    //array_push($productData,$slug);
                                    $productData[$product->category] =$slug.'@'.$product->slug.'@'.'EAN'.'@'.$product->product_name.'@'. $product->model.'@'.$product->make.'@'.$product->id;
                                    $addEbayProductPri= new EbayProductSearchPriorities();
                                    $addEbayProductPri->product_id=$product->id;
                                    $addEbayProductPri->priorities=$slug.'@'.$product->slug.'@'.$product->ean.'@'.$product->product_name.'@'. $product->model.'@'.$product->make.'@'.$product->id;
                                    $addEbayProductPri->save();


                                }


                            }else{

                                $productData[$product->category] =$ebayProductPriorities->priorities;

                            }


                        }catch (\Exception $e){
                            $this->info($e->getMessage());
                            continue;
                        }

                    }
                } else if (strpos($product->slug, ' ') !== false) {
                    $eanEx = explode(' ', $product->slug);

                    foreach ($eanEx as $slug) {
                        try {

                            $ebayProductPriorities=EbayProductSearchPriorities::where('product_id',$product->id)->first();
                            if(is_null($ebayProductPriorities)){
                                // $this->info($j++);
                                $response = $client->get("https://api.ebay.com/buy/browse/v1/item_summary/search?q=" . strtolower($slug) . "&category_ids=" . $Category->eBay_category_id . "&filter=deliveryCountry:GB,itemLocationCountry:GB,buyingOptions:{FIXED_PRICE},price:[20],priceCurrency:GBP&sort=price",
                                    ['headers' => $headers]);
                                $data = $response->json();
                                if ($data['total'] > 0) {
                                    $productData[$product->category] = $slug.'@'.$product->slug.'@'.'EAN'.'@'.$product->product_name.'@'. $product->model.'@'.$product->make.'@'.$product->id;

                                    $addEbayProductPri= new EbayProductSearchPriorities();
                                    $addEbayProductPri->product_id=$product->id;
                                    $addEbayProductPri->priorities=$slug.'@'.$product->slug.'@'.$product->ean.'@'.$product->product_name.'@'. $product->model.'@'.$product->make.'@'.$product->id;
                                    $addEbayProductPri->save();
                                }
                            }else{

                                $productData[$product->category] =$ebayProductPriorities->priorities;

                            }

                        }catch (\Exception $e){
                            $this->info($e->getMessage());
                            continue;
                        }


                    }
                } else {
                    if ($product->slug !== "") {
                        try {


                            $ebayProductPriorities=EbayProductSearchPriorities::where('product_id',$product->id)->first();
                            if(is_null($ebayProductPriorities)){
                                //  $this->info($j++);
                                $response = $client->get("https://api.ebay.com/buy/browse/v1/item_summary/search?q=" . strtolower($product->slug) . "&category_ids=" . $Category->eBay_category_id . "&filter=deliveryCountry:GB,itemLocationCountry:GB,buyingOptions:{FIXED_PRICE},price:[20],priceCurrency:GBP&sort=price",
                                    ['headers' => $headers]);
                                $data = $response->json();
                                if ($data['total'] > 0) {
                                    // array_push($productData,$product->slug);
                                    $productData[$product->category] =$product->slug.'@'.$product->slug.'@'.'EAN'.'@'.$product->product_name.'@'. $product->model.'@'.$product->make.'@'.$product->id;

                                    $addEbayProductPri= new EbayProductSearchPriorities();
                                    $addEbayProductPri->product_id=$product->id;
                                    $addEbayProductPri->priorities=$product->slug.'@'.$product->slug.'@'.$product->ean.'@'.$product->product_name.'@'. $product->model.'@'.$product->make.'@'.$product->id;
                                    $addEbayProductPri->save();
                                }
                            }else{

                                $productData[$product->category] =$ebayProductPriorities->priorities;

                            }


                        }catch (\Exception $e){
                            $this->info($e->getMessage());
                            continue;
                        }


                    }
                }
            }
            if (!count($productData)) {
                try {

                    $ebayProductPriorities=EbayProductSearchPriorities::where('product_id',$product->id)->first();
                    if(is_null($ebayProductPriorities)){
                        //  $this->info($j++);
                        $response = $client->get("https://api.ebay.com/buy/browse/v1/item_summary/search?q=" . strtolower($product->product_name) . "&category_ids=" . $Category->eBay_category_id . "&filter=deliveryCountry:GB,itemLocationCountry:GB,buyingOptions:{FIXED_PRICE},price:[20],priceCurrency:GBP&sort=price", ['headers' => $headers]);
                        $data = $response->json();
                        if ($data['total'] > 0) {
                            $productData[$product->category] = $product->product_name.'@'.$product->slug.'@'.$product->ean.'@'.$product->product_name.'@'. $product->model.'@'.$product->make.'@'.$product->id;
                            $addEbayProductPri= new EbayProductSearchPriorities();
                            $addEbayProductPri->product_id=$product->id;
                            $addEbayProductPri->priorities=$product->product_name.'@'.$product->slug.'@'.$product->ean.'@'.$product->product_name.'@'. $product->model.'@'.$product->make.'@'.$product->id;
                            $addEbayProductPri->save();

                        }
                    }else{
                        $productData[$product->category] =$ebayProductPriorities->priorities;
                    }

                }catch (\Exception $e){
                    $this->info($e->getMessage());
                    continue;
                }


            }
            $finalProductData[] = $productData;
        }





        foreach ($finalProductData as $product){
            $finalArray=[];
            $finalData=[];

            foreach ($product as $categoryName => $value) {

                $valueExplode=explode('@',$value);
               // dd($valueExplode);

                $conditionExc=[];
                $conditionVeryGoodRef=[];
                $conditionGoodRef=[];
                $conditionForParts=[];
                $totalQty=0;

                $i++;

                try{

                    $category = Category::where('name', $categoryName)->whereNotNull('eBay_category_id')->first();


                    $response = $client->get("https://api.ebay.com/buy/browse/v1/item_summary/search?q=" . strtolower($valueExplode[0]) . "&category_ids=" . $category->eBay_category_id . "&filter=conditionIds:{2010|2020|2030|7000},deliveryCountry:GB,itemLocationCountry:GB,buyingOptions:{FIXED_PRICE},price:[20],priceCurrency:GBP&sort=price", ['headers' => $headers]);
                    $data = $response->json();


                    if ($data['total'] > 0) {
                        $raking = 0;

                        $availableStockExc=0;
                        $availableStockVeryGoodRef=0;
                        $availableStockGoodRef=0;
                        $availableStockForParts=0;
                        $network='';




                        foreach ($data['itemSummaries'] as $item) {

                            $totalQty=$data['total'];
                            $bestSeller='';
                            $sellerName='';

                            $raking++;
                            $productResponse = $client->get($item['itemHref']);
                            $productData = $productResponse->json();

                            if (isset($item['price']['convertedFromValue'])) {
                                $price = $item['price']['convertedFromValue'];
                            } else {
                                $price = isset($item['price']['value']) ? $item['price']['value'] : 0;
                            }


                            if(isset($item['seller']['username'])){
                                if (!in_array($item['seller']['username'], $sellerUserNameList)) {
                                    $bestSeller='';
                                    $sellerName =$item['seller']['username'];
                                }
                            }



                            if (count($sellerList)) {


                                if(isset($item['seller']['username'])){
                                    if(in_array($item['seller']['username'],$sellerUserNameList)){

                                        $bestSeller=$item['seller']['username'];
                                    }
                                }


                            }



                            $ebayNetwork = EbayNetwork::where('item_id', $item['itemId'])->first();

                            if (is_null($ebayNetwork)) {

                                foreach ($productData['localizedAspects'] as $localized) {
                                    if ($localized['name'] === "Network") {
                                        $network=str_replace(' ', '', $localized['value']);
                                    }
                                }

                                $addNetwork = EbayNetwork::firstOrNew([
                                    'item_id' => $item['itemId'],
                                ]);
                                $addNetwork->network=$network;
                                $addNetwork->save();

                            } else {
                                $network = $ebayNetwork->network;
                            }

                            if($item['conditionId']==="2010"){
                                if (isset($productData['estimatedAvailabilities'][0]['estimatedAvailableQuantity'])) {
                                    $availableStockExc += $productData['estimatedAvailabilities'][0]['estimatedAvailableQuantity'];
                                }
                                $conditionExc['Excellent - Refurbished'][]=[
                                    'condition'=>$item['condition'],
                                    'price'=>$price,
                                    'raking'=>$raking,
                                    'best_seller'=>$bestSeller!=''?$bestSeller:'',
                                    'seller_name'=>$sellerName,
                                    'available_stock'=>$availableStockExc,
                                    'network'=>$network
                                ];

                                $conditionExc['Excellent - Refurbished']['available_stock']=$availableStockExc;





                            }elseif($item['conditionId']==="2020"){
                                if (isset($productData['estimatedAvailabilities'][0]['estimatedAvailableQuantity'])) {
                                    $availableStockVeryGoodRef += $productData['estimatedAvailabilities'][0]['estimatedAvailableQuantity'];
                                }
                                $conditionVeryGoodRef['Very Good - Refurbished'][]=[
                                    'condition'=>$item['condition'],
                                    'price'=>$price,
                                    'raking'=>$raking,
                                    'best_seller'=>$bestSeller!=''?$bestSeller:'',
                                    'seller_name'=>$sellerName,
                                    'available_stock'=>$availableStockVeryGoodRef,
                                    'network'=>$network
                                ];

                                $conditionVeryGoodRef['Very Good - Refurbished']['available_stock']=$availableStockVeryGoodRef;


                            }elseif($item['conditionId']==="2030"){
                                if (isset($productData['estimatedAvailabilities'][0]['estimatedAvailableQuantity'])) {
                                    $availableStockGoodRef += $productData['estimatedAvailabilities'][0]['estimatedAvailableQuantity'];
                                }
                                $conditionGoodRef['Good - Refurbished'][]=[
                                    'condition'=>$item['condition'],
                                    'price'=>$price,
                                    'raking'=>$raking,
                                    'best_seller'=>$bestSeller!=''?$bestSeller:'',
                                    'seller_name'=>$sellerName,
                                    'available_stock'=>$availableStockGoodRef,
                                    'network'=>$network
                                ];

                                $conditionGoodRef['Good - Refurbished']['available_stock']=$availableStockGoodRef;

                            }elseif($item['conditionId']==="7000"){
                                if (isset($productData['estimatedAvailabilities'][0]['estimatedAvailableQuantity'])) {
                                    $availableStockForParts += $productData['estimatedAvailabilities'][0]['estimatedAvailableQuantity'];
                                }



                                $conditionForParts['For parts or not working'][]=[
                                    'condition'=>$item['condition'],
                                    'price'=>$price,
                                    'raking'=>$raking,
                                    'best_seller'=>$bestSeller!=''?$bestSeller:'',
                                    'seller_name'=>$sellerName,
                                    'available_stock'=>$availableStockForParts,
                                    'network'=>$network
                                ];

                                $conditionForParts['For parts or not working']['available_stock']=$availableStockForParts;
                            }
                        }
                    }




                   // print_r($conditionForParts);

                    array_push($finalArray,
                        $conditionExc,
                        $conditionVeryGoodRef,
                        $conditionGoodRef,
                        $conditionForParts
                    );



                   foreach ($finalArray as $ebayList){
                       $ExFirstBest='';
                       $ExBestSellerPrice='';
                       $ExBestNetwork='';
                       $ExBestRaking='';
                       $availableStock='';
                       $ExFirstSeller='';
                       $ExSecondSeller='';
                       $ExThirdSeller='';
                       $ExFirstPrice='';
                       $ExSecondPrice='';
                       $ExThirdPrice='';
                       $ExFirstNetwork='';
                       $ExSecondNetwork='';
                       $ExThirdNetwork='';


                       $ExFirstRaking='';
                       $ExSecondRaking='';
                       $ExThirdRaking='';
                       $finalCondition='';



                       foreach ($ebayList as $key=>$items){
                           $finalCondition=$key;

                           $availableStock=$ebayList[$key]['available_stock'];
                           $ExFirstSeller=isset($ebayList[$key][0]['seller_name'])?$ebayList[$key][0]['seller_name']:'';
                           $ExSecondSeller=isset($ebayList[$key][1]['seller_name'])?$ebayList[$key][1]['seller_name']:'';
                           $ExThirdSeller=isset($ebayList[$key][2]['seller_name'])?$ebayList[$key][2]['seller_name']:'';

                           $ExFirstPrice=isset($ebayList[$key][0]['price'])?$ebayList[$key][0]['price']:'';
                           $ExSecondPrice=isset($ebayList[$key][1]['price'])?$ebayList[$key][1]['price']:'';
                           $ExThirdPrice=isset($ebayList[$key][2]['price'])?$ebayList[$key][2]['price']:'';

                           $ExFirstNetwork=isset($ebayList[$key][0]['network'])?$ebayList[$key][0]['network']:'';
                           $ExSecondNetwork=isset($ebayList[$key][1]['network'])?$ebayList[$key][1]['network']:'';
                           $ExThirdNetwork=isset($ebayList[$key][2]['network'])?$ebayList[$key][2]['network']:'';


                           $ExFirstRaking=isset($ebayList[$key][0]['raking'])?$ebayList[$key][0]['raking']:'';
                           $ExSecondRaking=isset($ebayList[$key][1]['raking'])?$ebayList[$key][1]['raking']:'';
                           $ExThirdRaking=isset($ebayList[$key][2]['raking'])?$ebayList[$key][2]['raking']:'';



                           if(isset($ebayList[$key][0]['best_seller'])  && $ebayList[$key][0]['best_seller']!==""){
                               $ExFirstBest=$ebayList[$key][0]['best_seller'];
                               $ExBestSellerPrice=$ebayList[$key][0]['price'];
                               $ExBestNetwork=$ebayList[$key][0]['network'];
                               $ExBestRaking=$ebayList[$key][0]['raking'];
                               $ExFirstSeller='';
                               $ExFirstPrice='';
                               $ExFirstNetwork='';
                               $ExFirstRaking='';


                           }elseif(isset($ebayList[$key][1]['best_seller'])&& $ebayList[$key][1]['best_seller']!==""){
                               $ExFirstBest=$ebayList[$key][1]['best_seller'];
                               $ExBestSellerPrice=$ebayList[$key][1]['price'];
                               $ExBestNetwork=$ebayList[$key][1]['network'];
                               $ExBestRaking=$ebayList[$key][1]['raking'];


                               $ExSecondSeller='';
                               $ExSecondPrice='';
                               $ExSecondNetwork='';
                               $ExSecondRaking='';
                           }elseif(isset($ebayList[$key][2]['best_seller']) && $ebayList[$key][2]['best_seller']!==""){
                               $ExFirstBest=$ebayList[$key][2]['best_seller'];
                               $ExBestSellerPrice=$ebayList[$key][2]['price'];
                               $ExBestNetwork=$ebayList[$key][2]['network'];
                               $ExBestRaking=$ebayList[$key][2]['raking'];

                               $ExThirdSeller='';
                               $ExThirdPrice='';
                               $ExThirdNetwork='';
                               $ExThirdRaking='';
                           }

                       }

                       $finalData[]=[
                           'condition'=>$finalCondition,
                           'first_seller'=>$ExFirstSeller,
                           'second_seller'=>$ExSecondSeller,
                           'third_seller'=>$ExThirdSeller,
                           'first_price'=>$ExFirstPrice,
                           'second_price'=>$ExSecondPrice,
                           'third_price'=>$ExThirdPrice,
                           'first_network'=>$ExFirstNetwork,
                           'second_network'=>$ExSecondNetwork,
                           'third_network'=>$ExThirdNetwork,
                           'first_raking'=>$ExFirstRaking,
                           'second_raking'=>$ExSecondRaking,
                           'third_raking'=>$ExThirdRaking,
                           'best_seller'=>$ExFirstBest,
                           'best_seller_price'=>$ExBestSellerPrice,
                           'best_seller_network'=>$ExBestNetwork,
                           'best_seller_raking'=>$ExBestRaking,
                           'availableStock'=>$availableStock
                       ];
                   }

                    foreach ($finalData as $data){

                        if($data['condition'] !==''){
                            $avaregPrice = AveragePrice::firstOrNew([
                                'mpn' => $valueExplode[1],
                                'condition' => $data['condition']
                            ]);
                            $avaregPrice->product_name = $valueExplode[3];
                            $avaregPrice->ean = $valueExplode[2];
                            $avaregPrice->mpn = $valueExplode[1];
                            $avaregPrice->epid = '';

                            $avaregPrice->condition = $data['condition'];
                            $avaregPrice->best_price_from_named_seller =$data['best_seller_price'];
                            $avaregPrice->best_price_network = $data['best_seller_network'];
                            $avaregPrice->best_seller = $data['best_seller'];
                            $avaregPrice->best_seller_listing_rank =$data['best_seller_raking'] ;

                            $avaregPrice->first_best_price = $data['first_price'];
                            $avaregPrice->first_network = $data['first_network'];
                            $avaregPrice->first_seller = $data['first_seller'];
                            $avaregPrice->first_listing_rank = $data['first_raking'];

                            $avaregPrice->second_best_price =$data['second_price'];
                            $avaregPrice->second_network = $data['second_network'];
                            $avaregPrice->second_seller = $data['second_seller'];
                            $avaregPrice->second_listing_rank = $data['second_raking'];
                            $avaregPrice->third_best_price = $data['third_price'];
                            $avaregPrice->third_network = $data['third_network'];
                            $avaregPrice->third_seller = $data['third_seller'];
                            $avaregPrice->third_listing_rank = $data['third_raking'];

                            $avaregPrice->model_no =isset($valueExplode[4])?$valueExplode[4]:'' ;
                            $avaregPrice->category = $categoryName;
                            $avaregPrice->platform = Stock::PLATFROM_EBAY;
                            $avaregPrice->make = isset($valueExplode[5])?$valueExplode[5]:'' ;
                            $avaregPrice->product_id=$valueExplode[6];

                            $avaregPrice->total_qty = $totalQty;

                            $avaregPrice->est_top_50_stock_qty = $data['availableStock'];
                            $avaregPrice->save();



                        }


                    }


                }catch (\Exception $e){
                    $this->info($e->getMessage());
                    continue;

                }


            }

            $this->info($i);

        }







    }
}
