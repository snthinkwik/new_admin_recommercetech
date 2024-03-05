<?php

namespace App\Jobs\EbayRefunds;

use App\Models\EbayRefund;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreateCreditNote implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var EbayRefund $ebayRefund
     */
    protected $ebayRefund;

    /**
     * @var int $customerId
     */
    protected $customerId;

    /**
     * @var string $saleName
     */
    protected $saleName;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(EbayRefund $ebayRefund, $customerId, $saleName)
    {
        $this->ebayRefund = $ebayRefund;
        $this->customerId = $customerId;
        $this->saleName = $saleName;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if($this->ebayRefund->credit_note_number) {
            print("Credit Note Number not null");
            return true;
        }
        try {
            $result = app('App\Contracts\Invoicing')->createEbayRefundCreditNote($this->ebayRefund, $this->customerId, $this->saleName);
            $this->ebayRefund->credit_note_number = $result['id'];
            $this->ebayRefund->processed = 1;
            $this->ebayRefund->save();
        }
        catch (\Exception $e) {
            alert("ebayRefund\createCreditNote Exception ".$this->ebayRefund->id." | ".$e->getMessage());

            if ($this->job) {
                $this->job->delete();
            }
        }
    }
}
