<?php

namespace App\Console\Commands;

use App\Models\AveragePrice;
use App\Models\EbayProductSearchPriorities;
use Illuminate\Console\Command;

class Truncate_average_price_table extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'average_price:truncate';

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
        AveragePrice::truncate();
        EbayProductSearchPriorities::truncate();

        $this->info('table successfully truncate');
    }
}
