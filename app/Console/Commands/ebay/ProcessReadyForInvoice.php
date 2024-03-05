<?php namespace App\Console\Commands\ebay;

use App\Contracts\Invoicing;
use App\Jobs\ebay\CreateInvoice;use App\Jobs\Unlocks\Emails\UnknownNetwork;
use App\Models\EbayOrderItems;
use App\Models\EbayOrders;
use App\Models\User;
use Queue;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class ProcessReadyForInvoice extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'ebay:process-ready-for-invoice';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $customerId =76; //71 (dev)

        $cucstomer=User::where('invoice_api_id',$customerId)->first();




      //  $items = EbayOrderItems::limit(100)->whereNull('invoice_number')->limit(100)->get();
        $items=EbayOrderItems::where('id',602)->get();



        if(!count($items)) {
            $this->info("Nothing to Process");
            return;
        }

        $this->info("Found: ".$items->count());

     $saleName= getQuickBookServiceProductName($cucstomer->quickbooks_customer_category,"Standard",$cucstomer->location,null);


        $n = 0;
        foreach($items as $item) {

           $n++;
//            $record = [
//                'item_name' => $item->item_name,
//                'sku' => $item->item_sku,
//                'price' => $item->individual_item_price,
//                'delivery_price' => $item->order->postage_and_packaging
//            ];
//            $this->comment($item->id." | ".$record['item_name']." | ".$record['sku']." | ".$record['price']." | ".$record['delivery_price']);
//            Queue::pushOn('invoices', new CreateInvoice($item, $customerId, $saleName, Invoicing::DELIVERY_UK));
//
            dispatch(new CreateInvoice($item, $customerId, $saleName, Invoicing::DELIVERY_UK));

        }
        $this->question("Processed $n / ".$items->count());

    }

}
