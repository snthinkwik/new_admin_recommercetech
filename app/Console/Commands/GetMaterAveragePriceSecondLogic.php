<?php

namespace App\Console\Commands;

use App\Models\AveragePrice;
use App\Models\BackMarketAveragePrice;
use App\Models\MasterAveragePrice;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GetMaterAveragePriceSecondLogic extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'master:second-average-price';

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


        $masterAveragePriceList=[];
        $current_timestamp = Carbon::now();

        foreach ($ebayAveragePrice as $price){

            $backMarket=BackMarketAveragePrice::where('product_id',$price->product_id)->get();

            if(count($backMarket)){


                foreach ($backMarket as $back){
                    $divided=0;
                    if($price->best_price_from_named_seller>0){
                        $divided++;
                    }
                    if($price->first_best_price>0){
                        $divided++;
                    }
                    if($price->second_best_price>0){
                        $divided++;
                    }
                    if($price->third_best_price>0){
                        $divided++;
                    }

                    $best_price_from_named_seller=0;
                    $first_best_price=0;
                    $second_best_price=0;
                    $third_best_price=0;
                    if(!empty($price->best_price_from_named_seller)){
                        $best_price_from_named_seller=$price->best_price_from_named_seller;
                    }
                    if(!empty($price->first_best_price)){
                        $first_best_price=$price->first_best_price;
                    }
                    if(!empty($price->second_best_price)){
                        $second_best_price=$price->second_best_price;
                    }
                    if(!empty($price->third_best_price)){
                        $third_best_price=$price->third_best_price;
                    }

                    $averageEbay= ($best_price_from_named_seller+$first_best_price+$second_best_price+$third_best_price)/$divided;

                    $averageBackMarket=$back->price_for_buybox;
                    $averageMaster=($averageEbay+$averageBackMarket)/2;
                    $diff_pre=(($averageEbay-$averageBackMarket)/$averageMaster)*100;

                    $product=Product::find($price->product_id);

                    if(getBackMarketConditionAestheticGrade($back->condition)==="Excellent" && $price->condition ==="Excellent - Refurbished"){
                        $masterAveragePriceList[]=[
                            'category'=>$price->category,
                            'condition'=>$price->condition,
                            'product_name'=>$price->product_name,
                            'ean'=>$back->ean,
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
                    }else if( getBackMarketConditionAestheticGrade($back->condition)==="Good" && $price->condition ==="Very Good - Refurbished"){
                        $masterAveragePriceList[]=[
                            'category'=>$price->category,
                            'condition'=>$price->condition,
                            'product_name'=>$price->product_name,
                            'ean'=>$back->ean,
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
                            'id'=>$price->id,
                        ];
                    }else if(getBackMarketConditionAestheticGrade($back->condition)==="Fair" && $price->condition ==="Good - Refurbished"){
                        $masterAveragePriceList[]=[
                            'category'=>$price->category,
                            'condition'=>$price->condition,
                            'product_name'=>$price->product_name,
                            'ean'=>$back->ean,
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
