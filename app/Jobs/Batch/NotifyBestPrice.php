<?php

namespace App\Jobs\Batch;

use App\Models\BatchOffer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class NotifyBestPrice implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */

    /**
     * @var \App\Models\BatchOffer
     */
    protected $batchOffer;

    protected $bestPrice;

    public function __construct(BatchOffer $batchOffer, $bestPrice)
    {
        $this->batchOffer = $batchOffer;
        $this->bestPrice = $bestPrice;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $batchOffer = $this->batchOffer;
        $user = $batchOffer->user;

        $bestPrice = $this->bestPrice;

        $subject = "Update for offer on Batch $batchOffer->batch_id";

        Mail::send(
            'emails.batches.notify-best-price', compact('user', 'batchOffer', 'bestPrice'),
            function($message) use($user, $subject) {
                $message->subject($subject)
                    ->from(config('mail.sales_old_address'), config('mail.from.name'))
                    ->to($user->email, $user->full_name);
            }
        );

        $message = "Hi $user->first_name, the current best offer for batch $batchOffer->batch_id is $bestPrice. Please update us via What's App if you wish to increase your offer - +447535239003. Regards, Recomm";

        $txtlocal = app('App\Contracts\Txtlocal');
        $sms = $txtlocal->sendMessage($user->phone, $message);
    }
}
