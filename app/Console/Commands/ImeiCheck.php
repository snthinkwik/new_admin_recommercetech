<?php

namespace App\Console\Commands;

use App\Models\Stock;
use App\Models\StockLog;
use GuzzleHttp\Client;
use Illuminate\Console\Command;

class ImeiCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'imei:check';

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
        $stock = Stock::where('network', Stock::NETWORK_SIM_LOCKERD)
            ->where('grade', Stock::GRADE_FULLY_WORKING)
            ->where('test_status', 'Complete')
            ->whereNotIn('status', [
                Stock::STATUS_LOST,
                Stock::STATUS_SOLD,
                Stock::STATUS_PAID,
                Stock::STATUS_DELETED
            ])->get();


        foreach ($stock as $item) {
            $checkIMEI = '';
            if (!empty($item->imei)) {
                $checkIMEI = $item->imei;
            } else {
                $checkIMEI = $item->serial;
            }


            $client = new Client();
            $response = $client->get("https://alpha.imeicheck.com/api/php-api/create?key=" . config('services.imei_check_api_key') . "&service=" . config('services.imei_check_service_code') . "&imei=" . $checkIMEI);
            $data = $response->json();






            if ($data['status'] === "success") {
                $stockNetworkUpdate = Stock::find($item->id);

                if (isset($data['object']['simlock'])) {
                    if ($data['object']['simlock']) {
                        $stockNetworkUpdate->network = Stock::NETWORK_SIM_LOCKERD;
                    } else {
                        $stockNetworkUpdate->network = Stock::NETWORK_CHECK_UNLOCKED;
                    }
                }

                $stockNetworkUpdate->save();

                StockLog::create([
                    'user_id' => 'ImeiCheck Cron Job',
                    'stock_id' => $stockNetworkUpdate->id,
                    'content' =>"Update Network:-" . $stockNetworkUpdate->network,
                ]);
//
            }


        }
        $this->info("IMEI Check Successfully");
    }
}
