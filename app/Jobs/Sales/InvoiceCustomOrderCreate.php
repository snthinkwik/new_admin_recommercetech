<?php

namespace App\Jobs\Sales;

use App\Models\Sale;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Invoice;

class InvoiceCustomOrderCreate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Sale
     */
    protected $sale;

    /**
     * @var User
     */
    protected $customer;

    /**
     * @var string
     */
    protected $saleName;

    protected $fee;


    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Sale $sale, User $customer, $saleName, $fee = null)
    {
        $this->sale = $sale;
        $this->customer = $customer;
        $this->saleName = $saleName;
        $this->fee = $fee;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $invoice_details = [
                'type' => 'InvoiceCustomOrderCreate',
                'sale' => $this->sale->id,
                'customerUser' => $this->customer->id,
                'saleName' => $this->saleName,
                'deliveryName' => null,
                'batch' => null,
                'price' => null,
                'auction' => null,
            ];
            $this->sale->invoice_details = $invoice_details;
            $this->sale->save();
        } catch (Exception $e) {
            print($e);
        }


        try {
            $this->sale->invoice_creation_status = Invoice::CREATION_STATUS_IN_PROGRESS;
            $this->sale->save();

            $result = app('App\Contracts\Invoicing')->createCustomOrderInvoice($this->sale, $this->customer, $this->saleName, $this->fee);

            $this->sale->invoice_api_id = $result['id'];
            $this->sale->invoice_number = $result['number'];
            $this->sale->invoice_total_amount = $result['amount'];
            $this->sale->invoice_creation_status = Invoice::CREATION_STATUS_SUCCESS;
            $this->sale->invoice_description = 'Invoice created';
            if($this->fee) {
                $this->sale->card_processing_fee = true;
            }
            $this->sale->save();

//            Queue::pushOn('emails', new EmailSend($this->sale, EmailSend::EMAIL_CREATED));

            dispatch(new EmailSend($this->sale, EmailSend::EMAIL_CREATED));

        }
        catch (Exception $e) {
            $this->sale->invoice_creation_status = Invoice::CREATION_STATUS_ERROR;
            $this->sale->invoice_description = $e;
            $this->sale->save();

            if ($this->job) {
                $this->job->delete();
            }
        }
    }
}
