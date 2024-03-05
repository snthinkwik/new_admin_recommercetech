<?php

namespace App\Console\Commands\ebay;

use App\Models\EbayFees;
use App\Models\EbayOrderLog;
use App\Models\EbayOrderItems;
use App\Models\EbayFeesLog;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class MatchedOldestOrderItemNumber extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'ebay:update-ebay-fee-matched-oldest-item-number';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Match ebay fee item number against ebay order item for buy it now and older order';

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
        $eBayFees = EbayFees::where("item_number", "!=", "")
                ->where("matched", EbayFees::MATCHED_NO)
                ->whereIn("fee_type", [
//                    'Item Subtitle Fee',
                    'Picture Pack Fee',
                    'International Site Visibility Fee',
                    'Gallery Plus Fee',
                    'Insertion Fee;Promotional rate'
                ])
                ->get();

        if ($eBayFees->count() > 0) {
            foreach ($eBayFees as $fee) {
                $Order = \App\EbayOrders::with("EbayOrderItems")
                        ->whereHas('EbayOrderItems', function($q) use($fee) {
                            $q->where("item_number", $fee->item_number)
                            ->where("sale_type", EbayOrderItems::SALE_TYPE_BUY_IT_NOW);
                        })
                        ->orderBy("sale_date", "ASC")
                        ->first();

                if (!is_null($Order)) {
                    echo $fee->item_number . "---", $Order->sales_record_number . "\n";

                    $fee->matched = EbayFees::MATCHED_YES;
                    $fee->sales_record_number = $Order->sales_record_number;

                    $ChangeEbayFees = '';
                    if ($fee->isDirty()) {
                        foreach ($fee->getAttributes() as $key => $value) {
                            if ($value !== $fee->getOriginal($key) && !checkUpdatedFields($value, $fee->getOriginal($key))) {
                                $orgVal = $fee->getOriginal($key);
                                $ChangeEbayFees .= "Changed \"$key\" from \"$orgVal\" to \"$value\".\n";
                            }
                        }
                    }

                    $fee->save();

                    if (!empty($ChangeEbayFees)) {
                        $eBayFeeLog = new EbayFeesLog();
                        $eBayFeeLog->fees_id = $fee->id;
                        $eBayFeeLog->content = $ChangeEbayFees;
                        $eBayFeeLog->save();
                    }

                    $changesBayOrder = $fee->fee_type . ' of ' . $fee->amount . " has been matched with this order";
                    $ebayOrdersLogModel = new EbayOrderLog();
                    $ebayOrdersLogModel->orders_id = $Order->id;
                    $ebayOrdersLogModel->content = $changesBayOrder;
                    $ebayOrdersLogModel->save();
                }
            }
        }
    }

}
