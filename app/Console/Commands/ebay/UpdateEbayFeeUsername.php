<?php

namespace App\Console\Commands\ebay;

use App\Models\EbayFees;
use App\Models\EbayOrderLog;
use App\Models\EbayFeesLog;
use App\Models\EbayOrderItems;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class UpdateEbayFeeUsername extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'ebay:update-ebay-fee-matched-username';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description.';

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
        $OrderItem = EbayOrderItems::with("order")
                ->whereHas('order', function($q) {
                    $q->where("ebay_username", "!=", "");
                })
                ->where("item_number", "!=", "")
                ->get();

        if ($OrderItem->count() > 0) {
            foreach ($OrderItem as $item) {
                $eBayFee = EbayFees::where(
                                [
                                    "ebay_username" => trim($item->order->ebay_username),
                                    "item_number" => $item->item_number,
                                    "matched" => EbayFees::MATCHED_NO
                                ]
                        )
                        ->get();

                if ($eBayFee->count() > 0) {
                    foreach ($eBayFee as $fee) {
                        $isUpdate = true;
                        if (in_array($fee->fee_type, ["Final Value Fee", "Ad fee", "Final Value Fee on Shipping"])) {
                            if (date('d-m-Y', strtotime($item->order->sale_date)) != date('d-m-Y', strtotime($fee->date))) {
                                $isUpdate = false;
                            }
                        }
                        if ($isUpdate) {
                            $fee->matched = EbayFees::MATCHED_YES;
                            $fee->sales_record_number = $item->sales_record_number;

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
                            $ebayOrdersLogModel->orders_id = $item->order_id;
                            $ebayOrdersLogModel->content = $changesBayOrder;
                            $ebayOrdersLogModel->save();
                        }
                    }
                }
            }
        }
    }

}
