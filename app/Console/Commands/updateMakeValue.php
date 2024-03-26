<?php

namespace App\Console\Commands;

use App\Models\AveragePrice;
use Illuminate\Console\Command;

class updateMakeValue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:make';

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
        $allData=AveragePrice::get();

        //    dd($allData);

        foreach ($allData as $data){

            $averagePrice=AveragePrice::find($data->id);
            //dd($averagePrice);

            if(preg_match('(iPad|iPhone)', $averagePrice->product_name) === 1) {
                $averagePrice->make="Apple";
            }elseif(strpos($averagePrice->product_name, 'Galaxy') !== false){
                $averagePrice->make="Samsung";
            }


            $averagePrice->save();

            $this->info("Average Price Id".$averagePrice->id.'Make Has been Updated As '.$averagePrice->make);




        }
    }
}
