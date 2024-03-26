<?php

namespace App\Console\Commands;

use App\Models\Stock;
use Illuminate\Console\Command;

class RemoveInBoundStock extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remove:in-bound-stock';

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
        $stockList=Stock::where('status',Stock::STATUS_INBOUND)->get();

        $i=0;
        foreach ($stockList as $stock){
            $i++;
            $stock=Stock::find($stock->id);
            $this->info("Stock:-". $stock->id."successfully deleted...");
            $stock->delete();

        }

        $this->info("Total ".$i." In Bound Stock Deleted");
    }
}
