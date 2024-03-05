<?php

namespace App\Console\Commands\ebay;

use App\Models\DpdInvoice;
use App\Models\DpdLog;
use App\Models\EbayOrders;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class AssignSalesRecordNumber extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'ebay:assign-sales-record-number';

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
        $ebayOrder = EbayOrders::has('DpdInvoice')->get();
        foreach ($ebayOrder as $ebay) {
            $dpdImport = DpdInvoice::firstOrNew([
                        'parcel_number' => $ebay->tracking_number
            ]);
            $dpdImport->matched = $ebay->sales_record_number;


            $ChangeDpd = '';
            if ($dpdImport->isDirty()) {
                foreach ($dpdImport->getAttributes() as $key => $value) {
                    if ($value !== $dpdImport->getOriginal($key) && !checkUpdatedFields($value, $dpdImport->getOriginal($key))) {
                        $orgVal = $dpdImport->getOriginal($key);
                        $ChangeDpd .= "Changed \"$key\" from \"$orgVal\" to \"$value\".\n";
                    }
                }
            }

            $dpdImport->save();

            if (!empty($ChangeDpd)) {
                $dpdLog = new DpdLog();
                $dpdLog->dpd_import_id = $dpdImport->id;
                $dpdLog->content = $ChangeDpd;
                $dpdLog->save();
            }
        }
    }

}
