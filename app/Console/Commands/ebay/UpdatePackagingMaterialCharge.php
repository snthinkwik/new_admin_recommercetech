<?php

namespace App\Console\Commands\ebay;

use App\Models\EbayOrders;
use App\Models\EbayOrderLog;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class UpdatePackagingMaterialCharge extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'ebay:update-packaging-material-charge';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Packaging Material Charge to 0.30 if order status = Dispatched';

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
        $EbayOrder = EbayOrders::with("EbayOrderItems")
                ->where('status', EbayOrders::STATUS_DISPATCHED)
                ->whereNull("packaging_materials")
                ->get();
        if ($EbayOrder->count() > 0) {
            foreach ($EbayOrder as $Order) {

                $Order->packaging_materials = 0.30 * $Order->EbayOrderItems->count();

                $ChangeEbayOrder = '';
                if ($Order->isDirty()) {
                    foreach ($Order->getAttributes() as $key => $value) {
                        if ($value !== $Order->getOriginal($key) && !checkUpdatedFields($value, $Order->getOriginal($key))) {
                            $orgVal = $Order->getOriginal($key);
                            $ChangeEbayOrder .= "Changed \"$key\" from \"$orgVal\" to \"$value\".\n";
                        }
                    }
                }

                $Order->save();

                if (!empty($ChangeEbayOrder)) {
                    $this->comment($ChangeEbayOrder);

                    $ebayOrdersLogModel = new EbayOrderLog();
                    $ebayOrdersLogModel->orders_id = $Order->id;
                    $ebayOrdersLogModel->content = $ChangeEbayOrder;
                    $ebayOrdersLogModel->save();
                }
            }
        }
    }

}
