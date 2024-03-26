<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FailedJobsCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'failed-jobs-check';

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
        $count = DB::table('failed_jobs')->count();
        if ($count) {
            alert("There are $count failed jobs.");
        }
    }
}
