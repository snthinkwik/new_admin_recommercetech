<?php

namespace App\Console\Commands\ebay;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use App\Models\EbayFees;

class ChangeDateFormateEbayFee extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'ebay:fee-change-date-format';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Change eBay Fee Date Format';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        $EbayFee = EbayFees::get();
        if ($EbayFee->count() > 0) {
            foreach ($EbayFee as $f) {
                if ($f['fee_type'] == "Final Value Fee") {
                    if (preg_match('/;(.*?) Final price/', $f['title'], $match) == 1) {
                        $f->ebay_username = trim($match[1]);
                    } else if (preg_match('/;(.*?) Best Offer price/', $f['title'], $match) == 1) {
                        $f->ebay_username = trim($match[1]);
                    }
                } elseif ($f['fee_type'] == "Ad fee") {
                    if (preg_match('/Sold to:(.*?). Sale Price/', $f['title'], $match) == 1) {
                        $f->ebay_username = trim($match[1]);
                    }
                }

                if (empty($f["amount"]) || trim($f["amount"]) == "£0.00" || strpos($f["amount"], '-') !== false)
                    $f['matched'] = "N/A";

                $f->formatted_fee_date = date("Y-m-d H:i:s", strtotime($f['date']));
                $f->save();
            }
        }
    }

}
