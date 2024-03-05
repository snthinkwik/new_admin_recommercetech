<?php namespace App\Console\Commands\ebay;

use App\EbayOrderItems;
use App\Stock;
use App\StockLog;
use Illuminate\Console\Command;


class AssignEbayOrderToStock extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'ebay:assign-order-stock';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Assign Ebay order to stock base on rct,imei,serial ';

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
//        $orderItemList=EbayOrderItems::where('item_sku','!=','')->get();
//
//        foreach ($orderItemList as $order){
//            $stockDetatils=Stock::whereNotIn('status',[Stock::STATUS_EBAY_LISTED])->where('imei',$order->item_sku)->orwhere('serial',$order->item_sku)->first();
//
//            if(!is_null($stockDetatils)){
//
//                if(!in_array($stockDetatils->id,['53833','50093','49622','49594','49577','48538','48258','45069','44458','41930','41043'])){
//                    $stock=Stock::find($stockDetatils->id);
//                    $stock->status= Stock::STATUS_ALLOCATED;
//
//                    if($stock->exists && $stock->isDirty()) {
//                        $changes = "";
//                        foreach ($stock->getAttributes() as $key => $value)
//                        {
//                            if ($value !== $stock->getOriginal($key) && checkUpdatedFields($value, $stock->getOriginal($key)))
//                            {
//                                $changes .= "Changed \"$key\" from \"{$stock->getOriginal($key)}\" to \"$value\".\n";
//                            }
//                        }
//                        if($changes) {
//                            StockLog::create([
//                                'stock_id' => $stock->id,
//                                'content' => $changes,
//                            ]);
//                        }
//                    }
//
//                    $stock->save();
//
//                    $orderSave=EbayOrderItems::find($order->id);
//                    $orderSave->stock_id=$stock->id;
//                    $orderSave->save();
//
//                    $this->info("Assign Ebay order to this stock id:-"."-".$stock->id);
//                }
//
//
//
//            }else{
////                echo "Not found:-". $order->item_sku;
////                echo "\n";
//                  $this->info("Not found".":-".$order->item_sku);
//            }
//
//
//        }
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */



}
