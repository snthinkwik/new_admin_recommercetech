<?php

namespace App\Console\Commands\ebay;

use App\Models\EbayFees;
use App\Models\EbayOrderLog;
use App\Models\EbayOrderItems;
use App\Models\EbayFeesLog;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class MatchedToOrderItemEbayFee extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'ebay:ebay-fee-matched-to-order-item';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check the item number of the fee vs. the item numbers for the sale records number in ebay_order_items.';

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

        $OrderItem = EbayOrderItems::whereNotNull("sales_record_number")
                ->whereNotNull("item_number")
                ->orderBy("id", "ASC")
                ->get();

        if ($OrderItem->count() > 0) {
            foreach ($OrderItem as $item) {
                $EbayFee = EbayFees::where("matched", EbayFees::MATCHED_YES)
                        ->where("item_number", $item->item_number)
                        ->where("sales_record_number", $item->sales_record_number)
                        ->whereNull("matched_to_order_item")
                        ->whereIn("fee_type", ["Final Value Fee", "Ad fee"])
                        ->orderBy("formatted_fee_date", "ASC")
                        ->first();

                if (!is_null($EbayFee)) {

                    $EbayFee->matched_to_order_item = $item->id;

                    $ChangeEbayFees = '';
                    if ($EbayFee->isDirty()) {
                        foreach ($EbayFee->getAttributes() as $key => $value) {
                            if ($value !== $EbayFee->getOriginal($key) && !checkUpdatedFields($value, $EbayFee->getOriginal($key))) {
                                $orgVal = $EbayFee->getOriginal($key);
                                $ChangeEbayFees .= "Changed \"$key\" from \"$orgVal\" to \"$value\".\n";
                            }
                        }
                    }

                    $EbayFee->save();

                    if (!empty($ChangeEbayFees)) {
                        $eBayFeeLog = new EbayFeesLog();
                        $eBayFeeLog->fees_id = $EbayFee->id;
                        $eBayFeeLog->content = $ChangeEbayFees;
                        $eBayFeeLog->save();
                    }
                }
            }
        }
    }

}
