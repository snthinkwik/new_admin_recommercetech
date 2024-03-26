<?php

namespace App\Console\Commands;

use App\Models\Stock;
use Illuminate\Console\Command;

class AddPhoneCheckCreateAtDate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'phone-check:create_at';

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
        $date = \Carbon\Carbon::today()->subDays(120);

        $stocks=Stock::where('created_at','>=',$date)->get();

        $i=0;
        foreach ($stocks as $stock){
            if(!is_null($stock->phone_check)){

                $i++;
                $update=Stock::find($stock->id);
                $update->phone_check_create_at=$stock->phone_check->created_device_date;
                $update->save();


                $this->info("This Stock ".$stock->id.' Phone Check Create At Successfully Updated');
            }
        }

        $this->info("Total ".$i." Stock Updated");
    }
}
