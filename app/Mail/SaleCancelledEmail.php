<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;



class SaleCancelledEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public  $invoice_number;
    public $customer;
    public $file_path;
    public $type_view;
    public $sale;
    public function __construct($typeView,$invoiceNumber,$sale,$customer,$filePath)
    {
        $this->invoice_number=$invoiceNumber;
        $this->customer=$customer;
        $this->file_path=$filePath;
        $this->type_view=$typeView;
        $this->sale=$sale;
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        return new Envelope(
            from:  new Address(config('mail.sales_address'), config('mail.from.name')) ,
            subject: "Order with " . config('app.company_name') . " has been cancelled - #$this->invoice_number",
            bcc: new Address(config('mail.sale_cc.address'), config('mail.sale_cc.name'))

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
            view:  $this->type_view ,
            with: [
                'customer'=>$this->customer,
                'sale'=>$this->sale
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments()
    {


        return [

            Attachment::fromPath($this->file_path)
        ];
    }
}
