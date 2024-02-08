<?php

namespace App\Console\Commands\ebay;

use App\Models\EbayFees;
use App\Models\EbayOrderItems;
use App\Models\EbayFeesLog;
use App\Models\ManualEbayFeeAssignment;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class AssignOwnerManuallyeBayFees extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'ebay:assign-owner-manually-ebay-fees';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manually Assign fees to company TRG if title included `EMPTY BOX RETAIL` AND matched is No';

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

        $EbayFees = EbayFees::where('title', 'like', "%EMPTY BOX RETAIL%")
                ->where("matched", EbayFees::MATCHED_NO)
                ->get();

        if ($EbayFees->count() > 0) {
            foreach ($EbayFees as $fee) {
                $ManuallyAssignFee = ManualEbayFeeAssignment::where("fee_record_no", $fee->id)->first();
                if (is_null($ManuallyAssignFee)) {
                    $ManuallyAssignFee = new ManualEbayFeeAssignment();
                }

                $ManuallyAssignFee->fee_record_no = $fee->id;
                $ManuallyAssignFee->fee_title = $fee->title;
                $ManuallyAssignFee->date = $fee->date;
                $ManuallyAssignFee->item_number = $fee->item_number;
                $ManuallyAssignFee->fee_type = $fee->fee_type;
                $ManuallyAssignFee->amount = $fee->amount;
                $ManuallyAssignFee->owner = EbayOrderItems::TRG;
                $ManuallyAssignFee->save();

                $fee->matched = EbayFees::MATCHED_MANUALLY_ASSIGNED;

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
            }
        }
    }

}
