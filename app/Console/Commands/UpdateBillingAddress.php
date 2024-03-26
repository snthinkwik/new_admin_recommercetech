<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class UpdateBillingAddress extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:billing-address';

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
        $users=User::all();

        foreach($users as $user){

            if($user->address && !$user->billingAddress){


                $billingAddress=new User\BillingAddress();
                $billingAddress->user_id=$user->id;
                $billingAddress->line1=$user->address->line1;
                $billingAddress->line2=$user->address->line2;
                $billingAddress->city=$user->address->city;
                $billingAddress->country=$user->address->country;
                $billingAddress->county=$user->address->county;
                $billingAddress->postcode=$user->address->postcode;
                $billingAddress->save();

                $this->info("user:-".$user->id." shipping address copy in billing address ");
            }



        }
    }
}
