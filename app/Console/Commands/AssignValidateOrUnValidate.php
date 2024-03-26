<?php

namespace App\Console\Commands;

use App\Models\MasterAveragePrice;
use Illuminate\Console\Command;

class AssignValidateOrUnValidate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'assign:validate-unvalidate';

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
        $masterAverage=MasterAveragePrice::all();
        foreach ($masterAverage as $master){

            $validationPer= getCategoryValidation($master->category);
            $average=MasterAveragePrice::find($master->id);
            if(abs($master->diff_percentage) <= $validationPer)
            {
                $average->validate='Yes';
            }else{
                $average->validate='No';
            }

            $this->info("Master Average:-".$average->id. "Assign Validate");

            $average->save();


        }
    }
}
