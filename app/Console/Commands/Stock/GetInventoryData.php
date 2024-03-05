<?php namespace App\Console\Commands\Stock;

use App\NewInventory;
use Illuminate\Http\Request;
use App\Stock;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class GetInventoryData extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'stock:inventory';

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

        NewInventory::truncate();
        $request=new Request();
        $stock = Stock::fromRequest($request, 'overview')
            ->join('products','products.id','=','new_stock.product_id')
            //->offset(($request->input('page', 1) - 1) * $perPage)
            ->whereNotNull('product_id')
            ->whereIn('status', ['In Stock',
                'Inbound',
                'Re-test',
                'Batch',
                'In Repair',
                '3rd Party',
                'Ready for Sale',
                'Retail Stock',
                'Listed on Auction',
                'Reserved for Order',
                'Allocated'
            ])
            ->get();

        $suffixes = [
            \App\Stock::STATUS_IN_STOCK => 'in_stock',
            \App\Stock::STATUS_RE_TEST => 're_test',
            \App\Stock::STATUS_BATCH => 'batch',
            \App\Stock::STATUS_REPAIR => 'in_repair',
            \App\Stock::STATUS_3RD_PARTY => "3rd_party",
            \App\Stock::STATUS_READY_FOR_SALE => "ready_for_sale",
            \App\Stock::STATUS_RETAIL_STOCK => "retail_stock",
            \App\Stock::STATUS_LISTED_ON_AUCTION => "listed_on_auction",
            \App\Stock::STATUS_RESERVED_FOR_ORDER => "reserved_for_order",
            \App\Stock::STATUS_ALLOCATED=>'allocated',
         //   \App\Stock::STATUS_INBOUND=>'inbound',
        ];



        foreach ($stock as $item) {
            $total=0;
            $totalPurchaseTotal = 0;
            $crackBack=0;
            $touchId=0;
            $unlocked=0;
            $totalQuantity=0;
            $testStatusInStock=0;
            $testStatusInbound=0;
            $testStatusRetest=0;
            $testStatusBatch=0;
            $testStatusInRepair=0;
            $testStatusParty=0;
            $testStatusReadyForSale=0;
            $testStatusRetailStock=0;
            $testStatusListedOnAuc=0;
            $testStatusReserved=0;
            $testStatusAllocated=0;
            $testStatus=0;
            $gradeA=0;
            $gradeB=0;
            $gradeC=0;
            $gradeD=0;
            $gradeE=0;
            $totalTestedItems=0;

            $skuStock = Stock::whereIn('product_id', [$item->product_id])->where('grade',$item->grade)->where('vat_type',$item->vat_type)->where('status', $item->status)->get();

            foreach ($skuStock as $sku) {
                $totalPurchaseTotal += $sku->total_cost_with_repair;
                if($sku->cracked_back==="Yes"){
                    $crackBack++;
                }
                if($sku->touch_id_working==="Yes"){
                    $touchId++;

                }

                if( $sku->condition ==="A"){
                    $gradeA++;

                }
                if( $sku->condition ==="B"){
                    $gradeB++;

                }
                if( $sku->condition ==="C"){
                    $gradeC++;

                }
                if( $sku->condition ==="D"){
                    $gradeD++;

                }
                if( $sku->condition ==="E"){
                    $gradeE++;

                }
                if(!in_array($sku->network,['Unlocked','']) ){
                    $unlocked++;

                }

                if($sku->test_status === "Complete" && $sku->status ==="In Stock"){
                    $testStatusInStock++;

                }
                if($sku->test_status === "Complete" && $sku->status ==="Inbound"){
                    $testStatusInbound++;

                }

                if($sku->test_status === "Complete" && $sku->status ==="Re-test"){
                    $testStatusRetest++;
                }

                if($sku->test_status === "Complete" && $sku->status ==="Batch"){
                    $testStatusBatch++;
                }

                if($sku->test_status === "Complete" && $sku->status ==="In Repair"){
                    $testStatusInRepair++;
                }

                if($sku->test_status === "Complete" && $sku->status ==="3rd Party"){
                    $testStatusParty++;
                }

                if($sku->test_status === "Complete" && $sku->status ==="Ready for Sale"){
                    $testStatusReadyForSale++;
                }

                if($sku->test_status === "Complete" && $sku->status ==="Retail Stock"){
                    $testStatusRetailStock++;
                }
                if($sku->test_status === "Complete" && $sku->status ==="Listed on Auction"){
                    $testStatusListedOnAuc++;
                }

                if($sku->test_status === "Complete" && $sku->status ==="Reserved for Order"){
                    $testStatusReserved++;
                }

                if($sku->test_status === "Complete" && $sku->status ==="Allocated"){
                    $testStatusAllocated++;
                }

                if($sku->test_status === "Complete"){
                    $testStatus++;

                }


                $totalTestedItems=$testStatusInStock+$testStatusInbound+$testStatusRetest+$testStatusBatch+$testStatusInRepair+$testStatusParty+$testStatusReadyForSale+$testStatusRetailStock+$testStatusListedOnAuc+$testStatusReserved+$testStatusAllocated;
                $totalQuantity+=$sku->quantity;
            }




            foreach ($suffixes as $tt){
                $total+=$item['count_'.$tt];
            }




            $item['total']=$total;
          //  $item['total_in_stock']=$paginator->total();
            $item['total_purchase_cost'] = $totalPurchaseTotal;
            $item['total_crack_back']=$crackBack;
            $item['total_touch_id']=$touchId;
            $item['total_locked']=$unlocked;
            $item['total_quantity']=$totalQuantity;
            $item['In_Stock']=$testStatusInStock;
            $item['Inbound']=$testStatusInbound;
            $item['Re_test']=$testStatusRetest;
            $item['Batch']=$testStatusBatch;
            $item['In_Repair']=$testStatusInRepair;
            $item['Party']=$testStatusParty;
            $item['Ready_for_Sale']=$testStatusReadyForSale;
            $item['Retail_Stock']=$testStatusRetailStock;
            $item['Listed_on_Auction']=$testStatusListedOnAuc;
            $item['Reserved_for_Order']=$testStatusReserved;
            $item['Allocated']=$testStatusAllocated;

            $item['gradeA']=$gradeA;
            $item['gradeB']=$gradeB;
            $item['gradeC']=$gradeC;
            $item['gradeD']=$gradeD;
            $item['gradeE']=$gradeE;
//            $totalQtyTotalLocked+=$unlocked;

        }


        foreach ($stock as $data){

            $totalTested=0;
            if($data->status === \App\Stock::STATUS_IN_STOCK){
                $totalTested=$data->In_Stock;
            }
            if($data->status === \App\Stock::STATUS_INBOUND){
                $totalTested=$data->Inbound;
            }
            if($data->status === \App\Stock::STATUS_RE_TEST){
                $totalTested=$data->Re_test;
            }
            if($data->status === \App\Stock::STATUS_BATCH){
                $totalTested=$data->Batch;
            }
            if($data->status === \App\Stock::STATUS_REPAIR){
                $totalTested=$data->In_Repair;
            }
            if($data->status === \App\Stock::STATUS_3RD_PARTY){
                $totalTested=$data->Party;
            }
            if($data->status === \App\Stock::STATUS_READY_FOR_SALE){
                $totalTested=$data->Ready_for_Sale;
            }
            if($data->status === \App\Stock::STATUS_RETAIL_STOCK){
                $totalTested=$data->Retail_Stock;
            }
            if($data->status === \App\Stock::STATUS_LISTED_ON_AUCTION){
                $totalTested=$data->Listed_on_Auction;
            }
            if($data->status === \App\Stock::STATUS_RESERVED_FOR_ORDER){
                $totalTested=$data->Reserved_for_Order;
            }
            if($data->status === \App\Stock::STATUS_ALLOCATED){
                $totalTested=$data->Allocated;
            }
            $inventory = NewInventory::firstOrNew([
                'product_id' => $data->product_id,
                'grade' => $data->grade,
                'status' => $data->status  ,
                'vat_type'=>$data->vat_type
            ]);

            $inventory->product_category=$data->product->category;
            $inventory->product_id=$data->product->id;
            $inventory->make=$data->product->make;
            $inventory->product_name=$data->product->product_name;
            $inventory->non_serialised=$data->non_serialised;
            $inventory->model=$data->product->model;
            $inventory->mpn=$data->product->slug;
            $inventory->ean=$data->product->ean;
            $inventory->grade=$data->grade;
            $inventory->status=$data->status;
            $inventory->vat_type=$data->vat_type;
            $inventory->total_purchase_price=$data->total_purchase_cost;
            $inventory->qty_in_stock=$data->total;
            $inventory->qty_in_tested=$totalTested;
            $inventory->qty_in_bound=$data->count_inbound;
            $inventory->grade_a=$data->gradeA;
            $inventory->grade_b=$data->gradeB;
            $inventory->grade_c=$data->gradeC;
            $inventory->grade_d=$data->gradeD;
            $inventory->grade_e=$data->gradeE;
            $inventory->cracked_back=$data->total_crack_back;
            $inventory->no_touch_face_id=$data->total_touch_id;
            $inventory->network_locked=$data->total_locked;
            $inventory->retail_comparison=$data->product->retail_comparison;
            $inventory->save();

        }







    }



}
