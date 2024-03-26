<?php

namespace App\Console\Commands;

use App\Models\BackMarketAveragePrice;
use Illuminate\Console\Command;

class RemoveBackMarketAvarage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remove:back-market-average-price';

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
        BackMarketAveragePrice::truncate();
        $this->info('table successfully truncate');
    }
}
