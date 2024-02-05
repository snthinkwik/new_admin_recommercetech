<?php

namespace App\Jobs\Unlocks\Emails;

use App\Mail\UnknownNetworkEmail;
use App\Models\Unlock;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\Message;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class UnknownNetwork implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $unlock;
    public function __construct(Unlock $unlock)
    {
        $this->unlock = $unlock;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        try{
            $email = null;
            if(isset($this->unlock->user_id)) {
                $name = $this->unlock->user->full_name;
                $email = $this->unlock->user->email;
            } elseif($this->unlock->ebay_user_email) {
                $email = $this->unlock->ebay_user_email;
                $name = null;
            }
        if(!$email) {
            return;
        }


            $sendEmail = new UnknownNetworkEmail($this->unlock);
            Mail::to($email)->send($sendEmail);

        }catch (\Exception $e){
            dd($e->getMessage());
        }

    }
}
