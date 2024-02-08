<?php namespace App\Console\Commands\PhoneCheck;

use App\NewInventory;
use App\PhoneCheck;
use App\PhoneCheckReports;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class GetReports extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'phone-check:report';

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
        $apis = ['App\Contracts\PhoneCheck'];


        foreach($apis as $api) {
            $pc = app($api);

            $phoneCheck=PhoneCheck::orderBy('id','desc')->get();


            foreach ($phoneCheck as $pho){

                $report=json_decode($pho->response);
               $res=$pc->getReports($report->A4Reports);
               $eraserReport=$pc->getReportsEraserReport($report->A4Reports);

            $phoneCheckReports = PhoneCheckReports::firstOrNew([
                'stock_id' => $pho->stock_id,
            ]);

            $phoneCheckReports->stock_id=$pho->stock_id;
            $phoneCheckReports->ean=!is_null($pho->imei)?$pho->imei:'';
            $phoneCheckReports->report_id=$report->A4Reports;
            $phoneCheckReports->report=$res;
            $phoneCheckReports->eraser_report=$eraserReport;
            $phoneCheckReports->save();

            $this->info(">>Add New Reports<<");
            $this->info("Stock Id:".$phoneCheckReports->stock_id);
            $this->info("EAN:".$phoneCheckReports->ean);
            $this->info("Report Id:".$phoneCheckReports->report_id);
                $this->info(">>>>>><<<<<<<<");
            }

        }









	}



}
