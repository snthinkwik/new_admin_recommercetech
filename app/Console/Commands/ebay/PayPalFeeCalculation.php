<?php

namespace App\Console\Commands\ebay;

use App\EbayOrders;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class PayPalFeeCalculation extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'ebay:paypal-fee-calculation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate PayPal Fee on Total Order + Shipping';

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
        $EbayOrder = EbayOrders::where('status', EbayOrders::STATUS_DISPATCHED)
                ->whereNull("paypal_fees")
                ->get();

        if ($EbayOrder->count() > 0) {
            foreach ($EbayOrder as $Order) {
                $Order->paypal_fees = round(((($Order["total_price"] + $Order["postage_and_packaging"]) * 2.9) / 100) + 0.20, 2);

                $ChangeBayOrder = '';
                if ($Order->isDirty()) {
                    foreach ($Order->getAttributes() as $key => $value) {
                        if ($value !== $Order->getOriginal($key) && !checkUpdatedFields($value, $Order->getOriginal($key))) {
                            $orgVal = $Order->getOriginal($key);
                            $ChangeBayOrder .= "Changed \"$key\" from \"$orgVal\" to \"$value\".\n";
                        }
                    }
                }

                $Order->save();

                if (!empty($ChangeBayOrder)) {
                    $ebayOrdersLogModel = new \App\EbayOrderLog();
                    $ebayOrdersLogModel->orders_id = $Order->id;
                    $ebayOrdersLogModel->content = $ChangeBayOrder;
                    $ebayOrdersLogModel->save();
                }
            }
        }
    }

}
