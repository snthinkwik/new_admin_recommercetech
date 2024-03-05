<?php

namespace App\Console\Commands\ebay;

use App\Models\EbayOrderItems;
use App\Models\TrgStock;
use Illuminate\Console\Command;

class UpdateTrgStatus extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'ebay:orderhub-update-trg-stock-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Change owner to TRG if IMEI match from TRG new_stock table.';

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


        $ItemSku = EbayOrderItems::where("owner", "")
                ->where("item_sku", "!=", "")
                ->get();

        if (count($ItemSku) > 0) {
            foreach ($ItemSku as $Item) {
                $CheckStock = TrgStock::where("imei", $Item->item_sku)
                        ->orWhere('serial', $Item->item_sku)
                        ->get()
                        ->count();

                if ($CheckStock > 0) {
                    $Item->owner = EbayOrderItems::TRG;

                    $ChangeOwner = '';
                    if ($Item->isDirty()) {
                        foreach ($Item->getAttributes() as $key => $value) {
                            if ($value !== $Item->getOriginal($key) && !checkUpdatedFields($value, $Item->getOriginal($key))) {
                                $orgVal = $Item->getOriginal($key);
                                $ChangeOwner .= "Changed \"$key\" from \"$orgVal\" to \"$value\".\n";
                            }
                        }
                    }

                    $Item->save();

                    if (!empty($ChangeOwner)) {
                        $ebayOrdersLogModel = new EbayOrderLog();
                        $ebayOrdersLogModel->orders_id = $Item->order_id;
                        $ebayOrdersLogModel->content = $ChangeOwner;
                        $ebayOrdersLogModel->save();
                    }
                }
            }
        }
    }

}
