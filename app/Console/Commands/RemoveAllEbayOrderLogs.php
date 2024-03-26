<?php

namespace App\Console\Commands;

use App\Models\EbayOrderLog;
use Illuminate\Console\Command;

class RemoveAllEbayOrderLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'truncate:ebay-order-logs';

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
        $ebayOrderLogs=new EbayOrderLog();
        $ebayOrderLogs::truncate();

        $this->info("Remove all Ebay order log");
    }
}
