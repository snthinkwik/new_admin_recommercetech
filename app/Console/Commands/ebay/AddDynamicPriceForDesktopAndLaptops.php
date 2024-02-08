<?php namespace App\Console\Commands\ebay;

use App\Models\AccessToken;
use App\Models\AveragePrice;
use App\Models\Category;
use App\Models\EbayProductDetails;
use App\Models\EBaySeller;
use App\Models\Product;
use App\Models\Stock;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use GuzzleHttp\Client;


class AddDynamicPriceForDesktopAndLaptops extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'ebay:dynamic-price-desktop-laptop';

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

	public function handle()
	{


        $productList = Product::select(['slug', 'product_name', 'category', 'ean', 'model','make','id'])->whereIn('category',['desktop','laptop'])->where('retail_comparison', 1)->where('slug', '!=', '')->orderBy('id', 'ASC')->get();
        $sellerList = EBaySeller::all();


        $sellerUserNameList = [];
        foreach ($sellerList as $seller) {
            array_push($sellerUserNameList, $seller->user_name);
        }


        $accessToken = AccessToken::where('platform', 'ebay-second')->first();

        $currentTime = Carbon::now();
        $addTime = \Carbon\Carbon::parse($accessToken->updated_at)->addSecond($accessToken->expires_in);


        $BasicHeaders = ebayBasicToken(config('services.ebay2.client_id'),config('services.ebay2.client_secret'));
        $this->comment("Initialization....");


        $conditionList = [
            '1000' => 'New',
            '1500' => 'Open box',
            '1750' => 'New with defects',
            '2000' => 'Certified - Refurbished',
            '2010' => 'Excellent - Refurbished',
            '2020' => 'Very Good - Refurbished',
            '2030' => 'Good - Refurbished',
            '2500' => 'Seller refurbished',
            '2750' => 'Like New',
            '3000' => 'Used',
            '4000' => 'Very Good',
            '5000' => 'Good',
            '6000' => 'Acceptable',
            '7000' => 'For parts or not working'
        ];


        $finalProductData = [];
        $client = new Client();
        $headers = [
            'Authorization' => "Bearer {$accessToken->access_token}",
            'X-EBAY-C-MARKETPLACE-ID'=>"EBAY_GB"
        ];
        foreach ($productList as $product) {
            $productData = [];

            if($product->category==="Desktop" && $product->make ==="Apple"){
                $categoryId='111418';
            }elseif($product->category==="Laptop" && $product->make ==="Apple"){
                $categoryId='111422';

            }else{
                $categoryId= $Category->eBay_category_id;
            }

            if (strpos($product->ean, ',') !== false) {
                $eanEx = explode(',', $product->ean);
                foreach ($eanEx as $ean) {


                    $response = $client->get("https://api.ebay.com/buy/browse/v1/item_summary/search?q=" . strtolower($ean) . "&category_ids=" . $categoryId . "&filter=deliveryCountry:GB,itemLocationCountry:GB,buyingOptions:{FIXED_PRICE},price:[20],priceCurrency:GBP&sort=price", [
                        'headers' => $headers
                    ]);
                    $data = $response->json();

                    if ($data['total'] > 0) {
                        // array_push($productData,$ean);
                        $productData[$product->category] =$ean.'-'.$product->slug.'-'.$product->ean.'-'.$product->product_name.'-'.
                        $product->id.'-'.$product->make;
                    }
                }
            } else if (strpos($product->ean, ' ') !== false) {
                $eanEx = explode(' ', $product->ean);
                foreach ($eanEx as $ean) {

                    $response = $client->get("https://api.ebay.com/buy/browse/v1/item_summary/search?q=" . strtolower($ean) . "&category_ids=" . $categoryId . "&filter=deliveryCountry:GB,itemLocationCountry:GB,buyingOptions:{FIXED_PRICE},price:[20],priceCurrency:GBP&sort=price", [
                        'headers' => $headers
                    ]);
                    $data = $response->json();
                    if ($data['total'] > 0) {
                        // array_push($productData,$ean);
                        $productData[$product->category] = $ean.'-'.$product->slug.'-'.$product->ean.'-'.$product->product_name.'-'.
                            $product->id.'-'.$product->make;
                    }
                }
            } else {
                if ($product->ean !== "") {

                    $response = $client->get("https://api.ebay.com/buy/browse/v1/item_summary/search?q=" . strtolower($product->ean) . "&category_ids=" . $categoryId . "&filter=deliveryCountry:GB,itemLocationCountry:GB,buyingOptions:{FIXED_PRICE},price:[20],priceCurrency:GBP&sort=price",
                        [
                        'headers' => $headers
                    ]);
                    $data = $response->json();

                    if ($data['total'] > 0) {
                        // array_push($productData,$product->ean);
                        $productData[$product->category] = $product->ean.'-'.$product->slug.'-'.$product->ean.'-'.$product->product_name.'-'.
                            $product->id.'-'.$product->make;
                    }
                }
            }



            if (!count($productData)) {


                if (strpos($product->slug, ',') !== false) {
                    $eanEx = explode(',', $product->slug);
                    foreach ($eanEx as $slug) {
                        $response = $client->get("https://api.ebay.com/buy/browse/v1/item_summary/search?q=" . strtolower($slug) . "&category_ids=" . $categoryId . "&filter=deliveryCountry:GB,itemLocationCountry:GB,buyingOptions:{FIXED_PRICE},price:[20],priceCurrency:GBP&sort=price",
                            ['headers' => $headers,]
                        );

                        $data = $response->json();

                        try {
                            if ($data['total'] > 0) {
                                //array_push($productData,$slug);
                                $productData[$product->category] =$slug.'-'.$product->slug.'-'.'EAN'.'-'.$product->product_name.'-'.$product->id.'-'.$product->make;
                            }
                        }catch (\Exception $e){
                            $this->info($e->getMessage());
                            continue;
                        }

                    }
                } else if (strpos($product->slug, ' ') !== false) {
                    $eanEx = explode(' ', $product->slug);
                    foreach ($eanEx as $slug) {

                        $response = $client->get("https://api.ebay.com/buy/browse/v1/item_summary/search?q=" . strtolower($slug) . "&category_ids=" . $categoryId . "&filter=deliveryCountry:GB,itemLocationCountry:GB,buyingOptions:{FIXED_PRICE},price:[20],priceCurrency:GBP&sort=price",
                            ['headers' => $headers]
                        );
                        $data = $response->json();

                        try {
                            if ($data['total'] > 0) {
                                //array_push($productData,$slug);
                                $productData[$product->category] = $slug.'-'.$product->slug.'-'.'EAN'.'-'.$product->product_name.'-'.$product->id.'-'.$product->make;
                            }
                        }catch (\Exception $e){
                            $this->info($e->getMessage());
                            continue;
                        }


                    }
                } else {
                    if ($product->slug !== "") {

                        $response = $client->get("https://api.ebay.com/buy/browse/v1/item_summary/search?q=" . strtolower($product->slug) . "&category_ids=" . $categoryId . "&filter=deliveryCountry:GB,itemLocationCountry:GB,buyingOptions:{FIXED_PRICE},price:[20],priceCurrency:GBP&sort=price",
                            ['headers' => $headers]
                        );
                        $data = $response->json();

                        try {
                            if ($data['total'] > 0) {
                                // array_push($productData,$product->slug);
                                $productData[$product->category] =$product->slug.'-'.$product->slug.'-'.'EAN'.'-'.$product->product_name.'-'.$product->id.'-'.$product->make;
                            }
                        }catch (\Exception $e){
                            $this->info($e->getMessage());
                            continue;
                        }


                    }
                }
            }
            if (!count($productData)) {


                $response = $client->get("https://api.ebay.com/buy/browse/v1/item_summary/search?q=" . strtolower($product->product_name) . "&category_ids=" . $categoryId . "&filter=deliveryCountry:GB,itemLocationCountry:GB,buyingOptions:{FIXED_PRICE},price:[20],priceCurrency:GBP&sort=price",
                    ['headers' => $headers]
                );
                $data = $response->json();

                try {
                    if ($data['total'] > 0) {
                        // array_push($productData,$product->product_name);
                        $productData[$product->category] = $product->product_name.'-'.$product->slug.'-'.$product->ean.'-'.$product->product_name.'-'.$product->id.'-'.$product->make;
                    }
                }catch (\Exception $e){
                    $this->info($e->getMessage());
                    continue;
                }


            }

            print_r($productData);
            $finalProductData[] = $productData;


        }


        foreach ($finalProductData as $data){
            foreach ($data as $categoryName => $value) {

                $valueExplode=explode('-',$value);

                $productCategoryId = Category::where('name', $categoryName)->whereNotNull('eBay_category_id')->first();
                foreach ($conditionList as $key => $conditionValue) {

                    if ($currentTime->gt($addTime)) {

                        $this->comment("Access Token is Expired....");
                        $this->comment("New Generated Access Token....");

                        $newAccessToken = getEbayRefreshTokenBaseToken($BasicHeaders, $accessToken->refresh_token);
                        $accessToken->access_token = $newAccessToken['access_token'];
                        $accessToken->expires_in = $newAccessToken['expires_in'];
                        $accessToken->save();
                        sleep(5);

                    }




                    if (!is_null($productCategoryId)) {

                        try {

                            $response = $client->get("https://api.ebay.com/buy/browse/v1/item_summary/search?q=" . strtolower($valueExplode[0]) . "&category_ids=" . $productCategoryId . "&filter=conditionIds:{" . $key . "},deliveryCountry:GB,itemLocationCountry:GB,buyingOptions:{FIXED_PRICE},price:[80],priceCurrency:GBP&sort=price",
                                ['headers' => $headers]
                            );
                            $data = $response->json();

                            $priceFirst = [];
                            $bestPrice = '';
                            $sellerPrice = [];
                            $priceList = [];
                            $rakingList = [];
                            $finalRaking = [];

                            $availableStock=0;
                            $totalQty=$data['total'];
                            if ($data['total'] > 0) {
                                $raking = 0;
                                if ($data['total'] > 0) {

                                    foreach ($data['itemSummaries'] as $item) {
                                        $raking++;
                                        $network="-";
                                        $productResponse = $client->get($item['itemHref']);
                                        $productData = $productResponse->json();

                                        if(isset($productData['estimatedAvailabilities'][0]['estimatedAvailableQuantity'])){
                                            $availableStock+=$productData['estimatedAvailabilities'][0]['estimatedAvailableQuantity'];
                                        }else{
                                            $availableStock+=0;
                                        }

                                        if (isset($item['price']['convertedFromValue'])) {
                                            $comparePrice = $item['price']['convertedFromValue'];
                                        } else {
                                            $comparePrice  = $item['price']['value'];
                                        }
                                        if (isset($item['price']['convertedFromValue'])) {
                                            $price = $item['price']['convertedFromValue'];
                                        } else {
                                            $price = isset($item['price']['value'])?$item['price']['value']:0;
                                        }

                                        if ($comparePrice > 20) {
                                            if (!in_array($item['seller']['username'], $sellerUserNameList)) {
                                                $priceList[$item['seller']['username'] . '@' . $price . '@' . $network] = $price;
                                                $rakingList[$item['seller']['username'] . '@' . $price . '@' . $raking] = $raking;
                                            }
                                        }

                                        if (count($sellerList)) {
                                            foreach ($sellerList as $seller) {
                                                if ($item['seller']['username'] === $seller->user_name) {
                                                    $priceFirst[$item['seller']['username'] . '@' . $price . '@' . $network . '@' . $raking] = $price;
                                                }
                                            }

                                        }

                                        $ebayProductDetatils = EbayProductDetails::firstOrNew([
                                            'item_id' => $item['itemId'],
                                        ]);
                                        $ebayProductDetatils->item_id = $item['itemId'];
                                        $ebayProductDetatils->mpn = $product->slug;
                                        $ebayProductDetatils->condition = $value;
                                        $ebayProductDetatils->user_name = $item['seller']['username'];
                                        $ebayProductDetatils->product_link = isset($item['itemGroupHref']) ? $item['itemGroupHref'] : $item['itemHref'];
                                        $ebayProductDetatils->save();
                                    }
                                }

                                if (count($priceFirst)) {
                                    $sellerUserNamePrice = array_search(min($priceFirst), $priceFirst);
                                    $bestPrice = explode('@', $sellerUserNamePrice);
                                }

                                arsort($rakingList);
                                foreach ($rakingList as $userKey => $fv) {
                                    $username = explode('@', $userKey);
                                    $finalRaking[$username[0] . '@' . $username[1]] = $fv;

                                }
                                asort($priceList);

                                $i = 0;
                                foreach ($priceList as $priceKey => $list) {
                                    if ($i <= 5) {
                                        array_push($sellerPrice, $priceKey);
                                    }

                                    $i++;
                                }

                                $result = array_unique($sellerPrice);
                                $exportFirst = isset($result[0]) ? explode('@', $result[0]) : [];
                                $exportSecond = isset($result[1]) ? explode('@', $result[1]) : [];
                                $exportThird = isset($result[2]) ? explode('@', $result[2]) : [];

                                $firstPrice = isset($exportFirst[1]) ? $exportFirst[1] : 0;
                                $firstSeller = isset($exportFirst[0]) ? $exportFirst[0] : '';
                                $firstNetwork = isset($exportFirst[2]) ? $exportFirst[2] : '';

                                $secondPrice = isset($exportSecond[1]) ? $exportSecond[1] : 0;
                                $secondSeller = isset($exportSecond[0]) ? $exportSecond[0] : '';
                                $secondNetwork = isset($exportSecond[2]) ? $exportSecond[2] : '';

                                $thirdPrice = isset($exportThird[1]) ? $exportThird[1] : 0;
                                $thirdSeller = isset($exportThird[0]) ? $exportThird[0] : '';
                                $thirdNetwork = isset($exportThird[2]) ? $exportThird[2] : '';


                                $firstRaking = '';
                                $secondRaking = '';
                                $thirdRaking = '';


                                asort($finalRaking);
                                if (array_key_exists($firstSeller . '@' . $firstPrice, $finalRaking)) {
                                    $firstRaking = $finalRaking[$firstSeller . '@' . $firstPrice];
                                }
                                if (array_key_exists($secondSeller . '@' . $secondPrice, $finalRaking)) {
                                    $secondRaking = $finalRaking[$secondSeller . '@' . $secondPrice];
                                }
                                if (array_key_exists($thirdSeller . '@' . $thirdPrice, $finalRaking)) {
                                    $thirdRaking = $finalRaking[$thirdSeller . '@' . $thirdPrice];
                                }

                                if ($firstPrice || $secondPrice || $thirdPrice) {
                                    $avaregPrice = AveragePrice::firstOrNew([
                                        'mpn' => $valueExplode[1],
                                        'condition' => $conditionValue
                                    ]);
                                    $avaregPrice->product_name = $valueExplode[3];
                                    $avaregPrice->ean = $valueExplode[2];
                                    $avaregPrice->mpn = $valueExplode[1];
                                    $avaregPrice->epid = '';
                                    $avaregPrice->condition = $conditionValue;
                                    $avaregPrice->best_price_from_named_seller = isset($bestPrice[1]) ? $bestPrice[1] : '';
                                    $avaregPrice->best_price_network = isset($bestPrice[2]) ? $bestPrice[2] : '';
                                    $avaregPrice->best_seller = isset($bestPrice[0]) ? $bestPrice[0] : '';
                                    $avaregPrice->best_seller_listing_rank = isset($bestPrice[3]) ? $bestPrice[3] : '';
                                    $avaregPrice->first_best_price = $firstPrice;
                                    $avaregPrice->first_network = $firstNetwork;
                                    $avaregPrice->first_seller = $firstSeller;
                                    $avaregPrice->first_listing_rank = $firstRaking;
                                    $avaregPrice->second_best_price = $secondPrice;
                                    $avaregPrice->second_network = $secondNetwork;
                                    $avaregPrice->second_seller = $secondSeller;
                                    $avaregPrice->second_listing_rank = $secondRaking;
                                    $avaregPrice->third_best_price = $thirdPrice;
                                    $avaregPrice->third_network = $thirdNetwork;
                                    $avaregPrice->third_seller = $thirdSeller;
                                    $avaregPrice->third_listing_rank = $thirdRaking;
                                    $avaregPrice->model_no = $product->model;
                                    $avaregPrice->category = $product->category;
                                    $avaregPrice->platform = Stock::PLATFROM_EBAY;
                                   // $avaregPrice->make=$product->make;
                                    $avaregPrice->total_qty=$totalQty;
                                    $avaregPrice->est_top_50_stock_qty=$availableStock;
                                    $avaregPrice->make=$valueExplode[5];
                                    $avaregPrice->product_id=$valueExplode[4];
                                    $avaregPrice->save();

                                    $this->info("New Recoded Added");
                                    $this->info("-------------------");

                                    $this->info("Id:-" . $avaregPrice->id);
                                    $this->info("Product Name:-" . $product->product_name);
                                    $this->info("Condition:-" . $value);
                                    $this->info("Best Price:-" . $avaregPrice->best_price_from_named_seller);
                                    $this->info("Best Seller Name:-" . $avaregPrice->best_seller);
                                    $this->info("Best Network:-" . $avaregPrice->best_price_network);
                                    $this->info("First Seller Price:-" . $avaregPrice->first_best_price);
                                    $this->info("First Seller:-" . $avaregPrice->first_seller);
                                    $this->info("First Network:-" . $avaregPrice->first_network);
                                    $this->info("Second Seller Price:-" . $avaregPrice->second_best_price);
                                    $this->info("Second Seller:-" . $avaregPrice->second_seller);
                                    $this->info("Second Network:-" . $avaregPrice->second_network);
                                    $this->info("Third Seller Price:-" . $avaregPrice->third_best_price);
                                    $this->info("Third Seller:-" . $avaregPrice->third_seller);
                                    $this->info("Third Network:-" . $avaregPrice->third_network);
                                    $this->info("Total Qty:-" . $totalQty);
                                }


                            }

                        } catch (\Exception $e) {

                            $this->info($e->getMessage());
                            continue;
                        }
                    }
                }

            }

        }

	}
}
