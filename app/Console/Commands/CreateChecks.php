<?php

namespace App\Console\Commands;

use App\Models\PhoneCheck;
use App\Models\PhoneCheckReports;
use App\Models\Stock;
use App\Models\StockLog;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;

class CreateChecks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'phone-check:create-checks {imei*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create phone checks for items with status "In Stock" and "Batch"';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $apis = ['App\Contracts\PhoneCheck'];


        ini_set('memory_limit', '1024M');
        foreach($apis as $api) {
            $pc = app($api);

            if ($this->argument('imei')) {

                $arguments = $this->argument('imei');
                $imei =$arguments[0] ;

                $this->question("Check IMEI: $imei");
                $res = $pc->check($imei);

                         if(isset($res->status)){
                                   if(!$res->status){
                                       $stock= Stock::where('imei',$imei)->orWhere('serial',$imei)->first();
                                       $reportList = "Test Status:-" . "Untested in Recomm system and Reports" . "\n";
                                       $stock->notes = $reportList;
                                       $stock->test_status=Stock::TEST_STATUS_UNTESTED;
                                       $stock->save();
                                   }

                          }

                if (is_object($res)) {
                    $result = [];
                    $result[] = $res;
                    $res = $result;
                }
            } else {
                $res = $pc->checkDate(Carbon::now()->format('Y-m-d'));

            }
            $this->question(gettype($res));

            if ($res) {

//			    if(!$res->status){
//                    $this->comment("No Data Found");
//			        return false;
//                }
//
                if(!isset($res->status)){


                    foreach ($res as $report) {



                        if(isset($report->master_id)){
                            $this->comment($report->master_id);
                            $this->question($report->IMEI);
                            // iPads have serial as IMEI
                            if (strlen($report->IMEI) != 15 && !is_numeric($report->IMEI)) {
                                $stock = Stock::where('serial', $report->IMEI)->first();
                            } else {
                                $stock = Stock::where('imei', $report->IMEI)->first();
                            }
                            if(is_null($stock)){
                                $stock = Stock::where('serial', $report->Serial)->first();
                                if(!is_null($stock)){
                                    $stock->imei=$report->IMEI;
                                    $stock->save();
                                }
                            }

                            if ($stock) {
                                $this->info("Stock: $stock->id");

                                $phoneCheckTest = PhoneCheck::where('imei', $report->IMEI)->whereNotNull('stock_id')->first();

                                if(!is_null($phoneCheckTest)){
                                    if($stock->status===Stock::STATUS_BATCH || $stock->status===Stock::STATUS_IN_STOCK ){

                                        $phoneCheckTest->status=PhoneCheck::STATUS_NEW;
                                        $phoneCheckTest->save();
                                    }
                                }
                                if ($phoneCheckTest && json_decode($phoneCheckTest->response)->DeviceUpdatedDate < $report->DeviceUpdatedDate){
                                    $this->comment('Updating Report');
                                    $old = json_decode($phoneCheckTest->response);

                                    $this->info("phone Check report getting it takes some time.....");
                                    $res=$pc->getReports($old->A4Reports);
                                    $eraserReport=$pc->getReportsEraserReport($old->A4Reports);
                                    $phoneCheckReports = \App\PhoneCheckReports::firstOrNew([
                                        'stock_id' => $stock->id,
                                    ]);
                                    $phoneCheckReports->stock_id= $stock->id;
                                    $phoneCheckReports->ean=!is_null($stock->imei)?$stock->imei:'';
                                    $phoneCheckReports->report_id=$old->A4Reports;
                                    $phoneCheckReports->report=$res;
                                    $phoneCheckReports->eraser_report=$eraserReport;
                                    $phoneCheckReports->save();

                                    $this->info("report added in phoneCheck report table");
                                    $phoneCheckTest->response = json_encode($report);
                                    $phoneCheckTest->status = PhoneCheck::STATUS_NEW;
                                    $phoneCheckTest->station_id = $report->StationID;
                                    $phoneCheckTest->no_updates = $phoneCheckTest->no_updates+1;
                                    $phoneCheckTest->save();

                                    $differences = (array_diff_recursive((array)$report, (array)$old));

                                    $differencesString = "Changes:";

                                    $this->info($report->IMEI);
                                    $this->info(count($differences));
                                    if(count($differences)>0){
                                        foreach ($differences as $key => $value) {

                                            if (is_string($key) && !is_array($value)) {
                                                $differencesString .= "<br/><b>$key</b>: $value";
                                            }
                                        }

                                        StockLog::create([
                                            'stock_id' => $stock->id,
                                            'content' => $differencesString,
                                            'user_id' => $phoneCheckTest->station_user_id
                                        ]);
                                    }

                                } elseif (!$phoneCheckTest) {
                                    $this->comment('Creating Report');

                                    //  dd($stock->id);

                                    $this->info("phone Check report getting it takes some time.....");
                                    $res=$pc->getReports($report->A4Reports);
                                    $eraserReport=$pc->getReportsEraserReport($report->A4Reports);


                                    $phoneCheckReports = PhoneCheckReports::firstOrNew([
                                        'stock_id' => $stock->id,
                                    ]);

                                    $phoneCheckReports->stock_id= $stock->id;
                                    $phoneCheckReports->ean=!is_null($stock->imei)?$stock->imei:'';
                                    $phoneCheckReports->report_id=$report->A4Reports;
                                    $phoneCheckReports->report=$res;
                                    $phoneCheckReports->eraser_report=$eraserReport;
                                    $phoneCheckReports->save();

                                    $this->info("report added in phoneCheck report table");

                                    $phoneCheck = new PhoneCheck();
                                    $phoneCheck->status = PhoneCheck::STATUS_NEW;
                                    $phoneCheck->imei = $report->IMEI;
                                    $phoneCheck->stock_id = $stock->id;
                                    $phoneCheck->station_id = $report->StationID;
                                    $phoneCheck->response = json_encode($report);
                                    $phoneCheck->no_updates = 1;
                                    $phoneCheck->save();
                                    StockLog::create([
                                        'stock_id' => $stock->id,
                                        'content' => json_encode($report),
                                        'user_id' => $phoneCheck->station_user_id
                                    ]);
                                }
                            }

                        }




                    }
                }

            }

        }


        artisan_call_background('phone-check:process-checks');

    }

    protected function getArguments()
    {
        return [
            ['imei', InputArgument::OPTIONAL, 'IMEI/Serial'],
        ];
    }
}
