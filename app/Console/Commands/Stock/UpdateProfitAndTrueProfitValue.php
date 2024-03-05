<?php namespace App\Console\Commands\Stock;

use App\Stock;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class UpdateProfitAndTrueProfitValue extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'update_profit_true_profit';

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
        ini_set('memory_limit', '1024M');
	    $stock=Stock::where('sale_price','>',0)->where('status',Stock::STATUS_IN_STOCK)->get();

	    foreach ($stock as $item){
	        $this->info($item->id);
            $stockObj=Stock::find($item->id);


            if($item->vat_type==="Standard" && $item->sale_price){

                $totalCosts=$item->total_cost_with_repair;

                $calculations= calculationOfProfit($item->sale_price,$totalCosts,$item->vat_type);

                $stockObj->sale_vat=$calculations['sale_vat'];
                $stockObj->total_price_ex_vat=$calculations['total_price_ex_vat'];
                $stockObj->profit=$calculations['profit'];
                $stockObj->true_profit=$calculations['true_profit'];
                $stockObj->marg_vat=null;


            }else if($item->sale_price){

                $totalCosts=$item->total_cost_with_repair;
                $calculations=calculationOfProfit($item->sale_price,$totalCosts,$item->vat_type,$item->purchase_price);
                $stockObj->profit= $calculations['profit'];
                $stockObj->marg_vat=$calculations['marg_vat'];
                $stockObj->true_profit=$calculations['true_profit'];
                $stockObj->sale_vat=$calculations['sale_vat'];
                $stockObj->total_price_ex_vat=$calculations['total_price_ex_vat'];

            }
            $stockObj->save();
	    }


        $this->info('All old data updated');




	}




}
