<?php

namespace App\Console\Commands\Orderhub;

use App\Models\EbayOrderLog;
use App\Models\EbayOrderItems;
use App\Models\EbayOrders;
use App\Models\Stock;
use App\Models\StockLog;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class EbayOrderAssignStock extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'orderhub:ebay-order-assign-stock';

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
    public function handle() {
        $OrderItem = EbayOrderItems::with("order")
                ->whereNull('stock_id')
                ->where('item_sku', '!=', '')
                ->whereHas('order', function($q) {
                    $q->whereIn('status', [EbayOrders::STATUS_NEW, EbayOrders::STATUS_AWAITING_PAYMENT]);
                })
                ->orderBy('id', 'asc')
                ->get();

        if (!count($OrderItem)) {
            $this->info("Nothing Found");
            return;
        }

        $ordersCount = $OrderItem->count();
        $matched = 0;
        $this->info("Order Items Found: $ordersCount");

        foreach ($OrderItem as $item) {
            $this->info("$item->id $item->order_id $item->item_sku ".$item->order->status." $item->stock_id");

            $stock = Stock::where('status', Stock::STATUS_RETAIL_STOCK)
                    ->where('new_sku', $item->item_sku)
                    ->orderBy('id', 'asc')
                    ->first();

            if ($stock) {
                $this->question("Item Found: $stock->id $stock->imei $stock->new_sku $stock->status");
                $item->stock_id = $stock->id;
                $item->save();

                EbayOrderLog::create([
                    'orders_id' => $item->order_id,
                    'content' => "<a href='" . route('stock.single', ['id' => $stock->id]) . "'>$stock->imei</a> has been reserved for this order"
                ]);

                $stock->status = Stock::STATUS_RESERVED_FOR_ORDER;
                $stock->save();
                StockLog::create([
                    'stock_id' => $stock->id,
                    'content' => "Reserved for eBay order <a href='" . route('admin.ebay-orders.view', ['id' => $item->order_id]) . "'>#$item->order_id</a>"
                ]);
                $matched++;
            }
        }

        $this->question("Matched: $matched / $ordersCount");
    }

}
