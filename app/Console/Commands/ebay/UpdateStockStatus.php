<?php

namespace App\Console\Commands\ebay;

use App\Models\EbayOrderItems;
use App\Models\EbaySaleHistory;
use App\Models\Stock;
use App\Models\StockLog;
use Illuminate\Console\Command;
use Auth;

class UpdateStockStatus extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'ebay:orderhub-update-stock-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update status to sold If IMEI match from stock table with Custom Label in master ebay table.';

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

        $ItemSku = EbayOrderItems::with(['order', 'stock'])
                ->has('stock', ">", 0)
                ->where("owner", "")
                ->where("item_sku", "!=", "")
                ->get();

        if (count($ItemSku) > 0) {
            foreach ($ItemSku as $Item) {
                if ($Item->stock->count() > 0) {
                    $Item->owner = EbayOrderItems::RECOMM;

                    $ChangeOwner = '';
                    if ($Item->isDirty()) {
                        foreach ($Item->getAttributes() as $key => $value) {
                            if ($value !== $Item->getOriginal($key) && !checkUpdatedFields($value, $Item->getOriginal($key))) {
                                $orgVal = $Item->getOriginal($key);
                                $ChangeOwner .= "Changed \"$key\" from \"$orgVal\" to \"$value\".\n";
                            }
                        }
                    }

                    $Item->save();

                    if (!empty($ChangeOwner)) {
                        $ebayOrdersLogModel = new \App\EbayOrderLog();
                        $ebayOrdersLogModel->orders_id = $Item->order_id;
                        $ebayOrdersLogModel->content = $ChangeOwner;
                        $ebayOrdersLogModel->save();
                    }

                    if (!in_array($Item->order->status, [\App\EbayOrders::STATUS_CANCELLED, \App\EbayOrders::STATUS_REFUNDED])) {
                        $stockSave = $Item->stock;

                        if (in_array($Item->stock->status, [Stock::STATUS_RETAIL_STOCK, Stock::STATUS_IN_STOCK, Stock::STATUS_READY_FOR_SALE, Stock::STATUS_LISTED_ON_AUCTION])) {
                            $stockSave->status = Stock::STATUS_SOLD;
                        }

                        $stockSave->sale_price = $Item->individual_item_price;
                        $stockSave->save(['override_sale_price' => 1]);

                        if (in_array($Item->stock->status, [Stock::STATUS_RETAIL_STOCK, Stock::STATUS_IN_STOCK, Stock::STATUS_READY_FOR_SALE, Stock::STATUS_LISTED_ON_AUCTION])) {
                            $ebayOrdersHistory = new EbaySaleHistory();
                            $ebayOrdersHistory->master_ebay_order_id = $Item->order_id;
                            $ebayOrdersHistory->order_no = $Item->sales_record_number;
                            $ebayOrdersHistory->price = $Item->individual_item_price;
                            $ebayOrdersHistory->stock_id = $stockSave->id;
                            $ebayOrdersHistory->status = $stockSave->status;
                            $ebayOrdersHistory->customer = 'eBay';
                            $ebayOrdersHistory->save();
                        }

                        if (in_array($Item->stock->status, [Stock::STATUS_RETAIL_STOCK, Stock::STATUS_IN_STOCK, Stock::STATUS_READY_FOR_SALE, Stock::STATUS_LISTED_ON_AUCTION])) {
                            StockLog::create([
                                'stock_id' => $stockSave->id,
                                'content' => 'Changed "Status" from ' . $Item->stock->status . ' to ' . Stock::STATUS_SOLD,
                            ]);
                        }
                    }
                }
            }
        }
    }

}
