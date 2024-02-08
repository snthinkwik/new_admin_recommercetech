<?php

namespace App\Console\Commands\ebay;

use App\EbayOrders;
use App\EbayRefund;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class RefundEbayOrders extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'ebay:refund-ebay-orders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Insert Refunded Order into new table.';

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
    public function fire() {
        $EbayOrders = EbayOrders::with("EbayOrderItems")
                ->where("status", EbayOrders::STATUS_REFUNDED)
                ->where("total_discount", "!=", 0.00)
                ->get();

        foreach ($EbayOrders as $Order) {

            $owner = "";

            $OwnerArray = array_values(array_filter(array_unique(array_column($Order->EbayOrderItems->toArray(), 'owner'))));

            if (count($OwnerArray) == 1) {
                $owner = $OwnerArray[0];
            }

            $EbayRefundModel = EbayRefund::firstOrNew([
                        'order_id' => $Order->id
            ]);

            $EbayRefundModel->order_id = $Order->id;
            $EbayRefundModel->sales_record_number = $Order->sales_record_number;
            $EbayRefundModel->refund_amount = $Order->total_discount;
            $EbayRefundModel->owner = $owner;
            $EbayRefundModel->save();
        }
    }

}
