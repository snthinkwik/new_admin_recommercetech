<?php

namespace App\Console\Commands\PhoneCheck;

use App\Models\Stock;
use Illuminate\Console\Command;

class PassIMEIInPhoneCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'call-phone-check-imei';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'call phone check imei';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $stockList=Stock::whereIn('status', [Stock::STATUS_IN_STOCK,Stock::STATUS_BATCH])->get();

        foreach ($stockList as $stock){

            if(!empty($stock->imei)){
                $this->info("pass IMEI number:-".$stock->imei);
                artisan_call_background("phone-check:create-checks $stock->imei");
            }else{
                $this->info("pass serial number:-".$stock->serial);
                artisan_call_background("phone-check:create-checks $stock->serial");
            }

        }
    }
}
