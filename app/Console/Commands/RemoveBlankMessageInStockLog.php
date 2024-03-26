<?php

namespace App\Console\Commands;

use App\Models\StockLog;
use Illuminate\Console\Command;

class RemoveBlankMessageInStockLog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remove:blank_message';

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
        $logs=StockLog::where("content","Changes:")->delete();

        if($logs){
            $this->info("Total ".count($logs)." Record Deleted");
        }else{
            $this->info("Total ".'zero'." Record Deleted");
        }

    }
}
