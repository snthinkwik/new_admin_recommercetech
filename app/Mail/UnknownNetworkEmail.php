<?php

namespace App\Mail;

use App\Models\Unlock;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;



class UnknownNetworkEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    protected $unlock;
    public function __construct( Unlock $unlock)
    {
        $this->unlock=$unlock;

    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
//        return new Envelope(
//            subject: 'is now being processed for unlock',
//        );

        return new Envelope(
            from: new Address(config('mail.sales_address'), config('mail.from.name')),
            subject: $this->unlock->imei.'is now being processed for unlock',

        );
    }

    /**
     * Get the message content definition.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    public function content()
    {
        return new Content(
            view: 'emails.unlocks.unknown-network',
            with: [
                'unlock' => $this->unlock,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments()
    {
        return [];
    }
}
