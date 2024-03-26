<?php

namespace App\Console\Commands;

use App\Models\ExceptionLog;
use Illuminate\Console\Command;

class RemoveAllExceptionLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'delete:exception-log';

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
        $exceptionLog=new ExceptionLog;
        $exceptionLog::truncate();

        $this->info("Remove all exception log");
    }
}
