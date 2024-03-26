<?php

namespace App\Console\Commands;

use App\Models\AveragePrice;
use App\Models\BackMarketAveragePrice;
use App\Models\MasterAveragePrice;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GetMaterAveragePrice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'master:average-price';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $ebayAveragePrice=AveragePrice::all();
        $backMarketAveragePrice=BackMarketAveragePrice::all();
        $current_timestamp = Carbon::now();
        $masterAveragePriceList=[];
        $this->comment("Initialization....");
        foreach ($ebayAveragePrice as $price){
            $product=Product::find($price->product_id);
            $ebayEANList=[];
            if(strpos($price->ean, ',') !== false){
                $eanEx = explode(',', $price->ean);
                foreach ($eanEx as $ean){
                    $trimSlug=  str_replace( array( '\'', '"', ',' , ';','–','‎','œur','-','Е6320','™','’’','•','’',' ','”','€','﻿','↑'), '', $ean);
                    array_push($ebayEANList,trim($trimSlug));
                }
            }elseif (strpos($price->ean, ' ') !== false){
                $eanEx = explode(',', $price->ean);
                foreach ($eanEx as $ean){
                    $trimSlug=  str_replace( array( '\'', '"', ',' , ';','–','‎','œur','-','Е6320','™','’’','•','’',' ','”','€','﻿','↑'), '', $ean);
                    array_push($ebayEANList,trim($trimSlug));
                }
            }else{
                $trimSlug=  str_replace( array( '\'', '"', ',' , ';','–','‎','œur','-','Е6320','™','’’','•','’',' ','”','€','﻿','↑'), '', $price->ean);
                array_push($ebayEANList,trim($trimSlug));
            }
            foreach ($backMarketAveragePrice as $back_market){
                $divided=0;
                if($price->best_price_from_named_seller){
                    $divided++;
                }
                if($price->first_best_price){
                    $divided++;
                }
                if($price->second_best_price){
                    $divided++;
                }
                if($price->third_best_price){
                    $divided++;
                }

                $best_price_from_named_seller=0;
                if(!empty($price->best_price_from_named_seller)){
                    $best_price_from_named_seller=$price->best_price_from_named_seller;
                }



                $averageEbay= ($best_price_from_named_seller+$price->first_best_price+$price->second_best_price+$price->third_best_price)/$divided;
                $averageBackMarket=$back_market->price_for_buybox;

                $this->info($back_market->id);
                $this->info($back_market->price_for_buybox);
                $averageMaster=($averageEbay+$averageBackMarket)/2;
                $diff_pre=(($averageEbay-$averageBackMarket)/$averageMaster)*100;

                if(in_array($back_market->ean,$ebayEANList)){
                    if(getBackMarketConditionAestheticGrade($back_market->condition)==="Excellent" && $price->condition ==="Excellent - Refurbished"){
                        $masterAveragePriceList[]=[
                            'category'=>$price->category,
                            'condition'=>$price->condition,
                            'product_name'=>$price->product_name,
                            'ean'=>$back_market->ean,
                            'model'=>$price->model,
                            'mpn'=>$price->mpn,
                            'average_master'=>$averageMaster,
                            'average_ebay'=>$averageEbay,
                            'average_back_market'=> $averageBackMarket,
                            'price_diff'=>$averageEbay-$averageBackMarket,
                            'diff_pre'=> $diff_pre,
                            'ma_product_id'=>$product->ma,
                            'make'=>$product->make,
                            'product_id'=>$product->id,

                        ];
                    }else if( getBackMarketConditionAestheticGrade($back_market->condition)==="Good" && $price->condition ==="Very Good - Refurbished"){
                        $masterAveragePriceList[]=[
                            'category'=>$price->category,
                            'condition'=>$price->condition,
                            'product_name'=>$price->product_name,
                            'ean'=>$back_market->ean,
                            'model'=>$price->model,
                            'mpn'=>$price->mpn,
                            'average_master'=>$averageMaster,
                            'average_ebay'=>$averageEbay,
                            'average_back_market'=> $averageBackMarket,
                            'price_diff'=>$averageEbay-$averageBackMarket,
                            'diff_pre'=> $diff_pre,
                            'ma_product_id'=>$product->ma,
                            'make'=>$product->make,
                            'product_id'=>$product->id
                        ];
                    }else if(getBackMarketConditionAestheticGrade($back_market->condition)==="Fair" && $price->condition ==="Good - Refurbished"){
                        $masterAveragePriceList[]=[
                            'category'=>$price->category,
                            'condition'=>$price->condition,
                            'product_name'=>$price->product_name,
                            'ean'=>$back_market->ean,
                            'model'=>$price->model,
                            'mpn'=>$price->mpn,
                            'average_master'=>$averageMaster,
                            'average_ebay'=>$averageEbay,
                            'average_back_market'=> $averageBackMarket,
                            'price_diff'=>$averageEbay-$averageBackMarket,
                            'diff_pre'=> $diff_pre,
                            'ma_product_id'=>$product->ma,
                            'make'=>$product->make,
                            'product_id'=>$product->id
                        ];
                    }
                }
            }
        }
        $this->comment("staring Add Data to database");
        foreach ($masterAveragePriceList as  $master){
            $averagePrice = MasterAveragePrice::firstOrNew([
                'ean' => $master['ean'],
                'condition' => $master['condition']
            ]);

            $averagePrice->category=$master['category'];
            $averagePrice->product_name=$master['product_name'];
            $averagePrice->ean=$master['ean'];
            $averagePrice->model=$master['model'];
            $averagePrice->mpn=$master['mpn'];
            $averagePrice->condition=$master['condition'];
            $averagePrice->master_average_price=$master['average_master'];
            $averagePrice->ebay_average_price=$master['average_ebay'];
            $averagePrice->bm_average_price=$master['average_back_market'];
            $averagePrice->price_diff=$master['price_diff'];
            $averagePrice->diff_percentage=$master['diff_pre'];
            $averagePrice->make=$master['make'];
            $averagePrice->ma_product_id=$master['ma_product_id'];
            $averagePrice->product_id=$master['product_id'];
            $averagePrice->ma_update_time=$current_timestamp;
            $averagePrice->type='Auto';
            $averagePrice->save();
            $this->info("category:-".$averagePrice->category);
            $this->info("Product Name:-".$averagePrice->product_name);
            $this->info("EAN:-".$averagePrice->ean);
            $this->info("Model No:-".$averagePrice->model);
            $this->info("MPN:-".$averagePrice->mpn);
            $this->info("Master Average Price:-".$averagePrice->master_average_price);
            $this->info("Ebay Average Price:-".$averagePrice->ebay_average_price);
            $this->info("Back Market Average Price-".$averagePrice->bm_average_price);
            $this->info("Price Different-".$averagePrice->price_diff);
            $this->info("Different Percentage -".$averagePrice->diff_percentage);
        }
    }
}
