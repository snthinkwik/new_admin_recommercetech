<?php

namespace App\Console\Commands\ebay;

use App\Models\EbayFees;
use App\Models\EbayOrderLog;
use App\Models\EbayOrderItems;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class ChangeTypeAuctionEbay extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'ebay:update-sale-type-auction-order';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Change Sale Type Auction';

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

        $OrderItem = EbayOrderItems::where("item_sku", "!=", "")
                ->where("sale_type", "")
                ->get();

        if ($OrderItem->count() > 0) {
            foreach ($OrderItem as $item) {
                $isValid = false;
                if (is_numeric($item->item_sku) && strlen($item->item_sku) == 15) {
                    $isValid = true;
                } else if (!preg_match('/[^0-9a-zA-Z\d]/', $item->item_sku) && strlen($item->item_sku) == 12) {
                    $isValid = true;
                }

                if ($isValid) {
                    $item->sale_type = EbayOrderItems::SALE_TYPE_AUCTION;

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
