<?php

namespace App\Console\Commands;

use App\Models\TrackingBackMarketDPDShipping;
use Illuminate\Console\Command;

class DeletedStatusDone extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'delete:status-done';

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
        $tracking_backmarket_dpd_shipping=TrackingBackMarketDPDShipping::whereNotNull('status')->get();
        foreach ($tracking_backmarket_dpd_shipping as $data){
            $tracking=TrackingBackMarketDPDShipping::find($data->id);
            $tracking->delete();
        }
    }
}
