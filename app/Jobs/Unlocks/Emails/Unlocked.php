<?php

namespace App\Jobs\Unlocks\Emails;

use App\Models\Unlock;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\Message;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

class Unlocked implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    /**
     * @var Collection
     */
    protected $unlock;

    /**
     * @var boolean
     */
    protected $codes;
    public function __construct(Unlock $unlock, $codes = false)
    {
        $this->unlock = $unlock;
        $this->codes = $codes;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->unlock->load('stock_item.sale.user');
        $user = null;
        $name = null;
        $email = null;
        if ($this->unlock->user_id) {
            $user = User::find($this->unlock->user_id);
            $name = $user->full_name;
            $email = $user->email;
        }
        elseif (!empty($this->unlock->stock_item->sale->user)) {
            $user = $this->unlock->stock_item->sale->user;
            $name = $user->full_name;
            $email = $user->email;
        }
        elseif(!empty($this->unlock->ebay_user_email)) {
            $user = $this->unlock->ebay_user_email;
            $email = $user;
        }

        if (!$user) {
            return;
        }

        $subject = "iPhone {$this->unlock->imei} is now unlocked from {$this->unlock->network}!";
        if($this->unlock->network == "Unknown" || $this->unlock->network == "unknown")
            $subject = "iPhone {$this->unlock->imei} is now unlocked!";

        Mail::send(
            'emails.unlocks.unlocked',
            ['user' => $user, 'unlock' => $this->unlock, 'codes' => $this->codes],
            function(Message $mail) use($user, $name, $email, $subject) {
                $mail->subject($subject)
                    ->to($email, $name)
                    ->bcc(config('mail.chris_eaton.address'), config('mail.chris_eaton.name'))
                    ->from(config('mail.sales_address'), config('mail.from.name'));
            }
        );
    }
}
