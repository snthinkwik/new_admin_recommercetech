<?php namespace App\Console\Commands\MobileAdvantage;

use App\Models\EbayOrderItems;
use App\Models\EbayOrders;
use App\Models\Stock;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class RemoveDuplicateDuplicate extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'remove:mobile-advantage';

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

	        $ebayOrder=EbayOrders::where('platform',Stock::PLATFROM_MOBILE_ADVANTAGE)->get();
	        foreach ($ebayOrder as $item){

	           // $this->info($item->id);
	            $ebayOrderItems=EbayOrderItems::where('order_id',$item->id)->get();
	            //$this->info(count($ebayOrderItems));

	            if(!count($ebayOrderItems)){
                    $this->info($item->id);
                    $delete=EbayOrders::find($item->id);
                    $delete->delete();
                    $this->info("Id:-".$item->id. "Deleted");
                }

//                foreach ($ebayOrderItems as $order){
//                    $delete=EbayOrderItems::find($order->id);
//                    $delete->delete();
//                    $this->info("Id:-".$order->id. "Deleted");
//                }

            }


	}



}
