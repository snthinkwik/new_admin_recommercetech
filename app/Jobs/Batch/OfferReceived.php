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

class OfferReceived implements ShouldQueue
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

    public function __construct(BatchOffer $batchOffer)
    {
        $this->batchOffer = $batchOffer;
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

        $subject = "Offer Received for Batch $batchOffer->batch_id";

        try {
            Mail::send(
                'emails.batches.offer-received', compact('user', 'batchOffer'),
                function ($message) use ($user, $subject) {
                    $message->subject($subject)
                        ->from(config('mail.sales_old_address'), config('mail.from.name'))
                        ->to($user->email, $user->full_name);
                }
            );
        } catch (\Exception $e) {
            alert("Batch Offer Received Email - $batchOffer->id Exception: ".$e->getMessage());
        }

        $message = "Hi $user->first_name, We have now received your offer of $batchOffer->offer_formatted for batch $batchOffer->batch_id. We will send you an update on your offer very shortly. Regards, Recomm";

        $txtlocal = app('App\Contracts\Txtlocal');
        $sms = $txtlocal->sendMessage($user->phone, $message);

        // send higher offer received email to outbidded users (check if it's highest offer)
        if(!BatchOffer::where('offer', '>', $batchOffer->offer)->where('batch_id', $batchOffer->batch_id)->count()) {
            $batchOfferOutbidded = BatchOffer::where('user_id', '!=', $user->id)->where('batch_id', $batchOffer->batch_id)->groupBy('user_id')->orderBy('offer', 'desc')->first();
            if($batchOfferOutbidded)
               // Queue::pushOn('emails', new HigherOfferReceived($batchOfferOutbidded, $batchOffer->offer_formatted));

                 dispatch(new HigherOfferReceived($batchOfferOutbidded, $batchOffer->offer_formatted));

            // send sms with url
            $batchOffers = BatchOffer::where('user_id', '!=', $user->id)->where('batch_id', $batchOffer->batch_id)->groupBy('user_id')->orderBy('offer', 'desc')->get();
            if(count($batchOffers)) {
                foreach($batchOffers as $offer) {
                    try {
                        $this->sendSms($offer, $batchOffer->offer_formatted);
                    } catch(\Exception $e) {
                        // do nothing
                    }
                }
            }
        }
    }
}
