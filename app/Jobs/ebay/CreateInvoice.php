<?php

namespace App\Jobs\ebay;

use App\Models\EbayOrderItems;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreateInvoice implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var EbayOrderItems $item
     */
    protected $item;

    /**
     * @var int
     */
    protected $customerId;

    /**
     * @var string|null
     */
    protected $deliveryName;

    /**
     * @var string
     */
    protected $saleName;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(EbayOrderItems $item, $customerId, $saleName, $deliveryName)
    {

        $this->item = $item;
        $this->customerId = $customerId;
        $this->saleName = $saleName;
        $this->deliveryName = $deliveryName;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if($this->item->invoice_number) {
            print("Invoice Number not null");
            return true;
        }
        try {

            $result = app('App\Contracts\Invoicing')->createEbayItemInvoice($this->item, $this->customerId, $this->saleName, $this->deliveryName);
            $this->item->invoice_number = $result['id'];
            $this->item->save();
        }
        catch (\Exception $e) {
            alert("ebay\createInvoice Exception ".$this->item->id." | ".$e->getMessage());

            if ($this->job) {
                $this->job->delete();
            }
        }

    }
}
