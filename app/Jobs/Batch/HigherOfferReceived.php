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

class HigherOfferReceived implements ShouldQueue
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

    /**
     * @var float
     */
    protected $highestOffer;

    public function __construct(BatchOffer $batchOffer, $highestOffer)
    {
        $this->batchOffer = $batchOffer;
        $this->highestOffer = $highestOffer;
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
        $highestOffer = $this->highestOffer;
        $subject = "Higher offer has been received for Batch $batchOffer->batch_id";

        try {
            Mail::send(
                'emails.batches.higher-offer-received', compact('user', 'batchOffer', 'highestOffer'),
                function ($message) use ($user, $subject) {
                    $message->subject($subject)
                        ->from(config('mail.sales_old_address'), config('mail.from.name'))
                        ->to($user->email, $user->full_name);
                }
            );
        } catch (\Exception $e) {
            alert("Batch Offer Received Email - $batchOffer->id Exception: ".$e->getMessage());
        }
    }
}
