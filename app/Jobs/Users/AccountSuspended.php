<?php

namespace App\Jobs\Users;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\Message;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class AccountSuspended implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $user;

    /**
     * @var string
     */
    protected $suspended;
    public function __construct(User $user, $suspended)
    {
        $this->user = $user;
        $this->suspended = $suspended;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if($this->user->suspended) {
            try {
                Mail::send(
                    'emails.users.account-suspended',
                    ['user' => $this->user, 'suspended' => $this->suspended],
                    function(Message $message) {
                        $message->subject("Your Recomm account has been suspended")
                            ->from(config('mail.sales_address'), config('mail.from.name'))
                            ->bcc(config('mail.sales_old_address'), config('mail.from.name'))
                            ->to($this->user->email, $this->user->full_name);
                    }
                );
            } catch(Exception $e) {
                alert("Account Suspended Exception: ".$e);
            };
        } else {
            try {
                Mail::send(
                    'emails.users.account-unsuspended',
                    ['user' => $this->user],
                    function(Message $message) {
                        $message->subject("Your account issues have been resolved.")
                            ->from(config('mail.sales_address'), config('mail.from.name'))
                            ->bcc(config('mail.sales_old_address'), config('mail.from.name'))
                            ->to($this->user->email, $this->user->full_name);
                    }
                );
            } catch(Exception $e) {
                alert("Account Unsuspended Exception: ".$e);
            };
        }
    }
}
