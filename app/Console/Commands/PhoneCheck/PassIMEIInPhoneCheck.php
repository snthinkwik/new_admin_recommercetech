<?php namespace App\Console\Commands\PhoneCheck;

use App\Stock;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class PassIMEIInPhoneCheck extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'call-phone-check-imei';

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

	    $stockList=Stock::whereIn('status',[Stock::STATUS_IN_STOCK,Stock::STATUS_BATCH])
                 ->whereNotIn('test_status',[Stock::TEST_STATUS_COMPLETE,Stock::TEST_STATUS_UNTESTED])->get();





	    foreach ($stockList as $stock){
            if(empty($stock->imei) && empty($stock->serial)){

                $this->info("pass LPN number:-".$stock->third_party_ref);
                artisan_call_background("phone-check:create-checks $stock->third_party_ref");

            }elseif(!empty($stock->imei)){
                $this->info("pass IMEI number:-".$stock->imei);
                artisan_call_background("phone-check:create-checks $stock->imei");
            }else{
                artisan_call_background("phone-check:create-checks $stock->serial");
            }



        }




	}



}
