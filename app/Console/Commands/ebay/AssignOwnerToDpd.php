<?php

namespace App\Console\Commands\ebay;

use App\Models\DpdLog;
use App\Models\EbayOrderItems;
use App\Models\EbayOrders;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Illuminate\Database\Eloquent\Builder;

class AssignOwnerToDpd extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'ebay:assign-owner-to-dpd';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign owner to DPD.';

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

        $EbayOrder = EbayOrders::with(["EbayOrderItems", "DpdImport" => function($dpd) {
                        return $dpd->where("owner", "");
                    }])
                ->whereHas('EbayOrderItems', function (Builder $query) {
                    $query->where('owner', '!=', '');
                })
                ->has("EbayOrderItems")
                ->has("DpdImport")
                ->get();

        if ($EbayOrder->count() > 0) {
            foreach ($EbayOrder as $Order) {
                $owner = "";

                $OwnerArray = array_values(array_filter(array_unique(array_column($Order->EbayOrderItems->toArray(), 'owner'))));

                if (count($OwnerArray) > 1) {
                    if (in_array(EbayOrderItems::RECOMM, $OwnerArray)) {
                        $owner = EbayOrderItems::RECOMM;
                    }
                } else {
                    $owner = $OwnerArray[0];
                }

                if (!empty($owner)) {
                    foreach ($Order["DpdImport"] as $dpd) {
                        $dpd->owner = $owner;

                        $ChangeDpd = '';
                        if ($dpd->isDirty()) {
                            foreach ($dpd->getAttributes() as $key => $value) {
                                if ($value !== $dpd->getOriginal($key) && !checkUpdatedFields($value, $dpd->getOriginal($key))) {
                                    $orgVal = $dpd->getOriginal($key);
                                    $ChangeDpd .= "Changed \"$key\" from \"$orgVal\" to \"$value\".\n";
                                }
                            }
                        }

                        $dpd->save();

                        if (!empty($ChangeDpd)) {
                            $dpdLog = new DpdLog();
                            $dpdLog->dpd_import_id = $dpd->id;
                            $dpdLog->content = $ChangeDpd;
                            $dpdLog->save();
                        }
                    }
                }
            }
        }
    }
}
