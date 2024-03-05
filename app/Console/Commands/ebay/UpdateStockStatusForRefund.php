<?php

namespace App\Console\Commands\ebay;

use App\Models\EbayOrders;
use App\Models\Stock;
use App\Models\StockLog;
use Illuminate\Console\Command;

class UpdateStockStatusForRefund extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'ebay:refund-update-stock-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Change Stock status In Stock if Item is refunded';

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
        $EbayOrders = EbayOrders::with('EbayOrderItems.stock')
                ->has('EbayOrderItems.stock')
                ->where("total_discount", "!=", 0.00)
                ->where("status", EbayOrders::STATUS_REFUNDED)
                ->get();


        if ($EbayOrders->count() > 0) {
            foreach ($EbayOrders as $order) {
                foreach ($order->EbayOrderItems as $Item) {
                    if ($Item->stock->status == Stock::STATUS_SOLD) {

                        $Item->stock->status = Stock::STATUS_IN_STOCK;
                        $Item->stock->save();

                        StockLog::create([
                            'stock_id' => $Item->stock->id,
                            'content' => "This item has returned and is now back in stock",
                        ]);
                    }
                }
            }
        }
    }

}
