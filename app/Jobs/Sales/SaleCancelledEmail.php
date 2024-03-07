<?php

namespace App\Jobs\Sales;

use App\Models\SaleLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\Message;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SaleCancelledEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */

    protected $sale;
    protected $newCustomer;
    protected $path;
    public function __construct($sale,$customer,$invoicePath)
    {
        $this->sale=$sale;
        $this->newCustomer = $customer;
        $this->path=$invoicePath;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $sale = $this->sale;
        //     $customer = $this->invoicing->getCustomer($this->sale->customer_api_id);

        $fullName=$this->newCustomer['first_name'].''.$this->newCustomer['last_name'];
        $email=$this->newCustomer['email'];

        if(!empty($this->newCustomer)) {
            alert("Can't send email to customer for sale \"{$sale->id}\" - customer not found.");
            return;
        }

        if (!$email) {
            alert("Can't send email to customer for sale \"{$sale->id}\" - email address empty.");
            return;
        }

        Mail::send(
           'emails.sales.cancelled',
            compact('sale'),
            function(Message $mail) use($fullName,$email, $sale) {
                $mail->subject("Order with " . config('app.company_name') . " has been cancelled - #$sale->invoice_number")
                    ->to('demo@gmail.com', 'demo');
            }
        );

        SaleLog::create([
            'sale_id' => $sale->id,
            'content' => "Email sent, subject: 'Order with ".config('app.company_name')." has been cancelled - #$sale->invoice_number'"
        ]);
    }
}
