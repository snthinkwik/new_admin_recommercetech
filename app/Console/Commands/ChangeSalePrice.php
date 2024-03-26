<?php

namespace App\Console\Commands;

use App\Models\Stock;
use Illuminate\Console\Command;

class ChangeSalePrice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'sale:change-price';

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
        $stocks=Stock::whereIn('serial',['F8QD201HLM94','DMPY9BBVLM94'])->get();

        foreach ($stocks as $item){
            $st=Stock::find($item->id);
            $st->sale_price='324.99';
            $st->save();


        }

        $this->info("Done");
    }
}
