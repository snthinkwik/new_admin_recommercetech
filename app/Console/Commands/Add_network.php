<?php

namespace App\Console\Commands;

use App\Models\Network;
use Illuminate\Console\Command;

class Add_network extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'network:add';

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
        $network=New Network();
        $network->pr_network='No Result';
        $network->country='Others';
        $network->save();

        $network1=New Network();
        $network1->pr_network='No Result';
        $network1->country='US';
        $network1->save();

        $network2=New Network();
        $network2->pr_network='No Result';
        $network2->country='UK';
        $network2->save();


        $this->info("Network Added Successfully");
    }
}
