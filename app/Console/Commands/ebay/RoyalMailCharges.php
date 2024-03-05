<?php

namespace App\Console\Commands\ebay;

use App\Models\EbayOrders;
use App\Models\DeliverySettings;
use App\Models\EbayDeliveryCharges;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class RoyalMailCharges extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'ebay:update-royal-mail-charge';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Royal Mail 1st charge in eBay Delivery Charges Table';

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
        $EbayOrder = EbayOrders::where('shipping_alias', "Royal Mail 1st")
                ->get();

        $DelivertSetting =DeliverySettings::where('carrier', "Royal Mail")
                ->where("service_name", "1st Class Large Letter")
                ->first();

        if ($EbayOrder->count() > 0 && !is_null($DelivertSetting)) {
            foreach ($EbayOrder as $Order) {

                $DeliveryCharge = EbayDeliveryCharges::where("sales_record_number", $Order->sales_record_number)
                        ->first();

                if (is_null($DeliveryCharge)) {
                    $DeliveryCharge = new EbayDeliveryCharges();

                    $DeliveryCharge->order_id = $Order->id;
                    $DeliveryCharge->sales_record_number = $Order->sales_record_number;
                    $DeliveryCharge->carrier = $DelivertSetting->carrier;
                    $DeliveryCharge->cost = $DelivertSetting->cost;
                    $DeliveryCharge->save();
                }
            }
        }
    }

}
