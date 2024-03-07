<?php

namespace App\Http\Controllers;

use App\Models\AccessToken;
use App\Models\AveragePrice;
use App\Models\EbayNetwork;
use App\Models\EbayOrderSoldDetails;
use App\Models\EbayProductDetails;
use App\Models\EbayProductSearchPriorities;
use App\Models\Product;
use App\Models\SellerFees;
use App\Models\Stock;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class AveragePriceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function getEbayIndex(Request $request)
    {

        $date = Carbon::now()->subDays(7);

//        $averagePrice = AveragePrice::with('getSoldItems')->whereHas('getSoldItems', function($q) use($date){
//        $q->where('created_at', '>=', $date);
//    })->fromRequest($request);

        $averagePrice = AveragePrice::fromRequest($request);
        $averagePrice = $averagePrice->paginate(config('app.pagination'))
            ->appends(array_filter(array_reverse($request->all())));


        $sellerFees=SellerFees::where('platform',Stock::PLATFROM_EBAY)->first();


        $itemsCount = AveragePrice::fromRequest($request)->get();

        $totalQty = AveragePrice::fromRequest($request)->sum('total_qty');
        $totalEstQty=AveragePrice::fromRequest($request)->sum('est_top_50_stock_qty');
        $total=count($itemsCount);

        $productMake=Product::select('make')->distinct()->get();



        if ($request->ajax()) {
            return response()->json([
                'itemsHtml' => View::make('average-price.list', compact('averagePrice','sellerFees','total','totalQty','totalEstQty','productMake'))->render(),
                'paginationHtml' => '' . $averagePrice->render(),
                'sort' => $request->sort,
                'sortO' => $request->sortO,
            ]);
        }


        $soldQty=[];


        foreach ($averagePrice as $items){


            if(count($items->getSoldItems))
                foreach ($items->getSoldItems as $soldItem){

                    $soldQty[$soldItem->average_price_id][]=$soldItem->sold_no;
                }
        }


        foreach ($averagePrice as $data){
            foreach ($soldQty as $key=>$value){
                if($data->id===$key){
                    $different=max($soldQty[$key])- min($soldQty[$key]);
                    $data['different']=$different;
                }

            }
        }





        return view('average-price.index',compact('averagePrice','sellerFees','total','totalQty','totalEstQty','productMake'));
    }


    public function getBackMarketIndex()
    {

        dd("In Progress");

//        $averagePrice=AveragePrice::paginate(config('app.pagination'));
//        return view('average-price.index',compact('averagePrice'));
    }


    public function removeAllDataFromTable(){
        AveragePrice::truncate();
        EbayOrderSoldDetails::truncate();
        EbayProductDetails::truncate();
        EbayNetwork::truncate();
        EbayProductSearchPriorities::truncate();

        dd("done");
    }

    public function getSoldItem($id){
        $ebayOrderSold=EbayOrderSoldDetails::find($id);
        $date = Carbon::now()->subDays(7);
        $soldDetails=EbayOrderSoldDetails::where('average_price_id',$ebayOrderSold->average_price_id)->where('created_at', '>=', $date)->get();

        return view('average-price.sold-item-history',compact('soldDetails'));


    }

    public function removeTabletAndComputer(){


        AveragePrice::whereIN('category', ['Tablet', 'Computer'])->delete();

        dd("Done");


    }

    public function advancedSearch(Request $request){

        $sellerFees=SellerFees::where('platform',Stock::PLATFROM_EBAY)->first();
        $fullProductName=strtoupper($request->product).' '. strtoupper($request->capacity).' '.strtoupper($request->color).' '. strtoupper($request->connectivity);

        $ebayData=[];

        $finalData=[];


        $requestCat=explode('-',$request->category);

        $accessToken=AccessToken::where('platform','ebay-second')->first();
        if($request->condition!==''){
            $condition=explode('-',$request->condition);
        }
        $validator = Validator::make($request->all(), [
            'product'=>'required',
            'category'=>'required',
            'make'=>'required'
        ]);


        if($validator->fails()){

            return[
                'status'=>400,
                'message'=>$validator->errors()->all()
            ];


        }
        if($request->make==="Apple" && $requestCat[1]==="Desktops"){
            $category= explode('-',"111418-Apple Desktops") ;
        }elseif($request->make==="Apple" && $requestCat[1]==="Laptop"){
            $category=explode('-',"111422-Laptop") ;
        }else{
            $category=explode('-',$request->category);
        }
        if("9355"===$category[0]){
            $fullProductName=strtoupper($request->product).' '. strtoupper($request->capacity).' '.strtoupper($request->color).' '. strtoupper($request->connectivity);
            if($request->condition!==''){
                $finalData= getEbayProductByMobileCategory($fullProductName,$request->product,$category[0],$category[1],$request->make,$condition[0],$accessToken->access_token,$request->capacity,$request->color,$request->connectivity,$condition[1]);
            }else{
                $finalData= getEbayProductByMobileCategory($fullProductName,$request->product,$category[0],$category[1],$request->make,$request->condition,$accessToken->access_token,$request->capacity,$request->color,$request->connectivity,$request->condition);
            }

        }elseif("171485"===$category[0]){
            $fullProductName=strtoupper($request->product).' '. strtoupper($request->capacity).' '.strtoupper($request->color).' '. strtoupper($request->connectivity);
            if($request->grade!==""){
                $finalData= getEbayProductWithOtherCategory($fullProductName,$request->product,$category[0],$category[1],$request->grade,$accessToken->access_token,$request->capacity,$request->color,$request->connectivity,$request->make);
            }else{
                $finalData=getEbayProductWithOtherCategory($fullProductName,$request->product,$category[0],$category[1],null,$accessToken->access_token,$request->capacity,$request->color,$request->connectivity,$request->make);
            }

        }elseif("179" ===$category[0] || "111418" ===$category[0] || "177" ===$category[0] || "111422" ===$category[0]){

            $fullProductName=strtoupper($request->product).' '. strtoupper($request->ram_size).' '.strtoupper($request->processor).' '. strtoupper($request->operating_system);
            $finalData= getProductBaseOnProductName($fullProductName,$request->product,$category[1],$accessToken->access_token,$category[0],
                $request->operating_system,$request->ram_size,$request->processor,$request->storage_type,$request->hard_drive,$request->ssd_capacity,$request->make);
        }
        $totalEstAvailable=0;
        $totalQty=0;
        if($finalData['status']===200){
            if(count($finalData['data'])>0){
                foreach ($finalData['data'] as $price){
                    $totalEstAvailable+=$price['available_stock'];
                    $totalQty+=$price['total_qty'];
                    $divided = 0;
                    if ($price['best_price_from_named_seller']) {
                        $divided++;
                    }
                    if ($price['first_best_price']) {
                        $divided++;
                    }
                    if ($price['second_best_price']) {
                        $divided++;
                    }


                    if ($price['third_best_price']) {
                        $divided++;
                    }

                    $average = ($price['best_price_from_named_seller'] + $price['first_best_price'] + $price['second_best_price'] + $price['third_best_price']) / $divided;
                    if ($average < 20) {
                        if (isset($sellerFees)) {
                            $shipping = $sellerFees['uk_shipping_cost_under_20'];
                        }

                    } else {
                        if (isset($sellerFees)) {
                            $shipping = $sellerFees['uk_shipping_cost_above_20'];
                        }

                    }

                    $perStd = ($average * $sellerFees->platform_fees) / 100;
                    $perMRG = ($average * $sellerFees->platform_fees) / 100;
                    $vatStd = (($average / 1.2) - ($perStd + $shipping + $sellerFees->accessories_cost_ex_vat)) * 0.80;
                    $vatMRG = (($average) - ($perMRG + $shipping + $sellerFees->accessories_cost_ex_vat)) * 0.76;
                    $ebayData[]=[
                        'category'=>$price['category'],
                        'product_name'=>$price['product_name'],
                        'model_no'=>$price['model_no'],
                        'mpn'=>$price['mpn'],
                        'condition'=>$price['condition'],
                        'average'=>money_format(config('app.money_format'),$average),
                        'best_price_from_named_seller'=> !empty($price['best_price_from_named_seller'])? money_format(config('app.money_format'),$price['best_price_from_named_seller']):'',
                        'best_price_network'=>$price['best_price_network'],
                        'best_seller'=>$price['best_seller'],
                        'best_seller_listing_rank'=>$price['best_seller_listing_rank'],
                        'first_best_price'=> !empty($price['first_best_price'])? money_format(config('app.money_format'),$price['first_best_price']):'' ,
                        'first_network'=>$price['first_network'],
                        'first_seller'=>$price['first_seller'],
                        'first_listing_rank'=>$price['first_listing_rank'],
                        'second_best_price'=> !empty($price['second_best_price'])?money_format(config('app.money_format'),$price['second_best_price']):'',
                        'second_network'=>$price['second_network'],
                        'second_seller'=>$price['second_seller'],
                        'second_listing_rank'=>$price['second_listing_rank'],
                        'third_best_price'=>!empty($price['third_best_price'])?money_format(config('app.money_format'),$price['third_best_price']):'',
                        'third_network'=>$price['third_network'],
                        'third_seller'=>$price['third_seller'],
                        'third_listing_rank'=>$price['third_listing_rank'],
                        'platform'=>$price['platform'],
                        'make'=>$price['make'],
                        'vatStd'=>money_format(config('app.money_format'),$vatStd),
                        'vatMRG'=>money_format(config('app.money_format'),$vatMRG),
                        'available_stock' =>$price['available_stock'],

                    ];
                }
                return [
                    'status'=>200,
                    'message'=>"successfully Get Data",
                    'data'=>$ebayData,
                    'total_qty'=>$totalQty,
                    'total_est'=>$totalEstAvailable
                ];
            }else{

                return[
                    'status'=>404,
                    'message'=>'Data Not Found'
                ];
            }
        }else{
            return[
                'status'=>500,
                'message'=>'Something Went Wrong. Please Try Again After Sometime',
                'error_message'=>$finalData['error']
            ];
        }
    }

    public function searchProductInfo(Request  $request){



        if($request->get('query'))
        {
            $query = $request->get('query');


            $averagePrice=AveragePrice::select('product_name','mpn','ean','model_no','make')->where('product_name','LIKE', "%{$query}%")
                ->orWhere('mpn','LIKE', "%{$query}%")
                ->orWhere('ean','LIKE', "%{$query}%")
                ->orWhere('model_no','LIKE', "%{$query}%")
                ->orWhere('make','LIKE', "%{$query}%")
                ->distinct()
                ->limit(10)->get();

            $output = '<ul class="dropdownMenu searchAutoBoxUl" >';

            if(count($averagePrice)>0)
            {
                foreach($averagePrice as $row)
                {
                    $output .= '
       <li><a href="#" class="searchAutoBox"> '.$row->make.' '.$row->product_name.'</a></li>
       ';
                }
            }else{

                $output .= '
       <li style="padding: 60px !important;"><strong>No Data Found</strong></li>';

            }


            $output .= '</ul>';
            return $output;
        }


    }

}
