<?php

namespace App\Console\Commands\ebay;

use App\Models\EbayFees;
use App\Models\EbayOrderLog;
use App\Models\EbayOrderItems;
use App\Models\EbayFeesLog;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class UpdateAuctionEbayFee extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'ebay:update-ebay-fee-matched-auction';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'If sale type is auction then match item number in ebay fee and update the sales record number.';

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

        $OrderItem = EbayOrderItems::with("order")->where("item_number", "!=", "")
                ->where("sale_type", EbayOrderItems::SALE_TYPE_AUCTION)
                ->get();

        if ($OrderItem->count() > 0) {
            foreach ($OrderItem as $item) {
                $eBayFee = EbayFees::where(
                                [
                                    "item_number" => $item->item_number,
                                    "matched" => EbayFees::MATCHED_NO
                                ]
                        )
                        ->get();

                if ($eBayFee->count() > 0) {
                    foreach ($eBayFee as $fee) {
                        $isUpdate = true;
                        if (trim($fee->fee_type) == "Insertion Fee") {
                            $SaleDate = new \DateTime(date("Y-m-d", strtotime($item->order->sale_date)));
                            $FeeDate = new \DateTime(date("Y-m-d", strtotime($fee->date)));
                            $diff = $FeeDate->diff($SaleDate)->format("%a");
                            if ($diff > 10) {
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
