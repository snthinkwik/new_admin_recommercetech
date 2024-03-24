<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class QueueWorkersCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue-workers-check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check if our queue workers are running and start them if they\'re not';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
//        $workerCommands = [
//            'php artisan queue:listen invoices --tries=1',
//            'php artisan queue:listen ebay-invoices --tries=2',
//            'php artisan queue:work import-external --tries=3',
//            'php artisan queue:listen emails --tries=2',
//            'php artisan queue:work ebay --tries=3',
//            'php artisan queue:work ebay_fee --tries=3',
//            'php artisan queue:work ebay_dpd --tries=3',
//            'php artisan queue:work dpd-shipping --tries=2',
//            'php artisan queue:listen recomme-shipping --tries=2'
//        ];
        $workerCommands = [
            'php artisan queue:work --tries=3',

        ];
        $processInfo = `ps aux`;
        foreach ($workerCommands as $workerCommand) {
            if (strpos($processInfo, $workerCommand) === false) {
                $this->comment("Starting `$workerCommand`");
                $cmd = "$workerCommand >/dev/null 2>/dev/null &";
                exec($cmd);
            }
        }
    }
}
