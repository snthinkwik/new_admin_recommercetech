<?php namespace App\Console\Commands\BackMarket;

use App\Models\BackMarketAveragePrice;
use App\Models\BackMarketEawData;
use App\Models\Product;
use Illuminate\Console\Command;
use GuzzleHttp\Client;


class AddDynamicPriceForBackMarket extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'back-market:dynamic-price';

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
        $productList = Product::select(['id','slug', 'product_name', 'category', 'ean', 'model','make','back_market_id'])->where('retail_comparison', 1)->orderBy('id', 'ASC')->get();



        $eanAndBackMarketId=[];

        $eanEx='';

        foreach ($productList as $product){


            $finalEAN='';
            $backMarketRawData='';


            if(!is_null($product->back_market_id)){



                    $eanEx=$product->back_market_id;
                    $backMarketRawData=BackMarketEawData::where('product_id',$product->back_market_id)->first();

                    if(!is_null($backMarketRawData)){


                        $backMarketAverage= BackMarketAveragePrice::firstOrNew([
                            'product_id' => $product->id,
                            'condition'=>$backMarketRawData->condition,
                        ]);
                        $backMarketAverage->product_id=$product->id;
                        $backMarketAverage->back_market_product_id=$backMarketRawData->product_id;
                        $backMarketAverage->condition=$backMarketRawData->condition;
                        $backMarketAverage->price=$backMarketRawData->price;
                        $backMarketAverage->price_for_buybox=$backMarketRawData->price_for_buybox;
                        $backMarketAverage->category=$product->category;
                        $backMarketAverage->product_name=$product->product_name;
                        $backMarketAverage->ean=$product->ean;
                        $backMarketAverage->model=$product->model;
                        $backMarketAverage->mpn=$product->slug;
                        $backMarketAverage->make=$product->make;
                        $backMarketAverage->recomme_product_id=$product->id;
                        $backMarketAverage->save();

                    }



            }else{



                if (strpos($product->ean, ',') !== false){
                    $eanEx = explode(',', trim($product->ean));


                }elseif(strpos($product->ean, ' ') !== false){

                    $eanEx = explode(' ', trim($product->ean));


                }elseif($product->ean !== ""){

                    $eanEx=$product->ean;
                }



                if(is_array($eanEx)){
                    foreach ($eanEx as $ean){


                        $trimSlug=  str_replace( array( '\'', '"', ',' , ';','–','‎','œur','-','Е6320','™','’’','•','’',' ','”','€','﻿','↑'), '', $ean);
                        $backMarketRawData=BackMarketEawData::where('ean',trim($trimSlug))->first();

                        if(!is_null($backMarketRawData)){


                            $backMarketAverage= BackMarketAveragePrice::firstOrNew([
                                'product_id' => $product->id,
                                'condition'=>$backMarketRawData->condition,
                            ]);
                            $backMarketAverage->product_id=$product->id;
                            $backMarketAverage->back_market_product_id=$backMarketRawData->product_id;
                            $backMarketAverage->condition=$backMarketRawData->condition;
                            $backMarketAverage->price=$backMarketRawData->price;
                            $backMarketAverage->price_for_buybox=$backMarketRawData->price_for_buybox;
                            $backMarketAverage->category=$product->category;
                            $backMarketAverage->product_name=$product->product_name;
                            $backMarketAverage->ean=$ean;
                            $backMarketAverage->model=$product->model;
                            $backMarketAverage->mpn=$product->slug;
                            $backMarketAverage->make=$product->make;
                            $backMarketAverage->recomme_product_id=$product->id;
                            $backMarketAverage->save();

                        }
                    }


                }else{

                    $trimSlug=  str_replace( array( '\'', '"', ',' , ';','–','‎','œur','-','Е6320','™','’’','•','’',' ','”','€','﻿','↑'), '', $eanEx);

                    $backMarketRawData=BackMarketEawData::where('ean',trim($trimSlug))->first();

                    if(!is_null($backMarketRawData)){
                        $backMarketAverage= BackMarketAveragePrice::firstOrNew([
                            'product_id' => $product->id,
                            'condition'=>$backMarketRawData->condition,
                        ]);
                        $backMarketAverage->product_id=$product->id;
                        $backMarketAverage->back_market_product_id=$backMarketRawData->product_id;
                        $backMarketAverage->condition=$backMarketRawData->condition;
                        $backMarketAverage->price=$backMarketRawData->price;
                        $backMarketAverage->price_for_buybox=$backMarketRawData->price_for_buybox;
                        $backMarketAverage->category=$product->category;
                        $backMarketAverage->product_name=$product->product_name;
                        $backMarketAverage->ean=$eanEx;
                        $backMarketAverage->model=$product->model;
                        $backMarketAverage->mpn=$product->slug;
                        $backMarketAverage->make=$product->make;
                        $backMarketAverage->recomme_product_id=$product->id;
                        $backMarketAverage->save();

                    }
                }
            }

        }

    }

}
