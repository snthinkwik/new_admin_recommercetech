<?php

namespace App\Console\Commands;

use App\Models\Stock;
use Illuminate\Console\Command;

class FindProductIdAsZero extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'product:assigned-null';

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
        ini_set('memory_limit', '1024M');

        $stockList=Stock::where('product_id',0)->get();

        foreach ($stockList as $stock){
            $stock=Stock::find($stock->id);
            $stock->product_id=null;
            $stock->save();

        }
        $this->info("all product id successfully change");
    }
}
