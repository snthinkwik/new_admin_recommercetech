<?php

namespace App\Console\Commands\ebay;

use App\Models\EbayOrders;
use App\Models\DeliverySettings;
use App\Models\EbayDeliveryCharges;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class HermesCharges extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'ebay:update-hermes-charge';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update hermes charge in eBay Delivery Charges Table';

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
        $EbayOrder = EbayOrders::where('shipping_alias', "Hermes")
                ->get();

        $DelivertSetting = DeliverySettings::where('carrier', "Hermes")
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
