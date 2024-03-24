<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class AddDemoAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'add:admin';

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
       $user= new User();
       $user->type='admin';
       $user->admin_type='admin';
       $user->customer_type='admin';
       $user->first_name='admin';
       $user->last_name='admin';
       $user->email='admin@demo.com';
       $user->email_confirmed=1;
       $user->company_name="Demo";
       $user->phone='0000';
       $user->location='UK';
       $user->business_description="Demo";
       $user->whatsapp=0;
       $user->whatsapp_added=0;
       $user->vat_registered=0;
       $user->password='$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' ;// password
        $user->save();

        $this->info("User Detatisl:-". print_r($user));

    }
}
