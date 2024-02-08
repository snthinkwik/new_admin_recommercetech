<?php

namespace App\Console\Commands\ebay;

use App\Models\EbayOrderItems;
use App\Models\EbaySku;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class AssignSKUs extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'ebay-orders:assign-ebay-sku';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign Owner depended on SKUs';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        $EbaySku = EbaySku::all();
        if ($EbaySku->count() > 0) {
            foreach ($EbaySku as $s) {
                $OrderItem = EbayOrderItems::where('item_sku', $s->sku)
                        ->where("owner", "")
                        ->get();

                if ($OrderItem->count() > 0) {
                    foreach ($OrderItem as $item) {
                        $item->owner = $s->owner;
                        $item->sale_type = EbayOrderItems::SALE_TYPE_BUY_IT_NOW;

                        $ChangeOwner = '';
                        if ($item->isDirty()) {
                            foreach ($item->getAttributes() as $key => $value) {
                                if ($value !== $item->getOriginal($key) && !checkUpdatedFields($value, $item->getOriginal($key))) {
                                    $orgVal = $item->getOriginal($key);
                                    $ChangeOwner .= "Changed \"$key\" from \"$orgVal\" to \"$value\".\n";
                                }
                            }
                        }
                        $item->save();

                        if (!empty($ChangeOwner)) {
                            $this->comment($ChangeOwner);

                            $ebayOrdersLogModel = new \App\EbayOrderLog();
                            $ebayOrdersLogModel->orders_id = $item->order_id;
                            $ebayOrdersLogModel->content = $ChangeOwner;
                            $ebayOrdersLogModel->save();
                        }
                    }
                }
            }
        }
    }

}
