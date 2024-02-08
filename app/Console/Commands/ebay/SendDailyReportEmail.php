<?php

namespace App\Console\Commands\ebay;

use App\EbayOrders;
use Illuminate\Console\Command;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Illuminate\Database\Eloquent\Builder;
use File;

class SendDailyReportEmail extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'ebay:send-daily-report-email';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Attachment: should be a CSV with all ebay orders in the last 24 hours where the Item Name contains Faulty';

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
    public function fire() {
        $ebayOrders = EbayOrders::with('EbayOrderItems')
                ->whereHas('EbayOrderItems', function (Builder $query) {
                    $query->where('item_name', 'like', "%Faulty%");
                })
                ->where('sale_date', '>', DB::raw('NOW() - INTERVAL 24 HOUR'))
                ->get();

        if ($ebayOrders->count() > 0) {
            $ebayOrderList = [];
            foreach ($ebayOrders as $item) {

                $ebayOrderList[] = [
                    'Sales Record No' => $item->sales_record_number,
                    'Buyer Name' => $item->buyer_name,
                    'Buyer Email' => $item->buyer_email,
                    'Address 1' => $item->buyer_address_1,
                    'Address 2' => $item->buyer_address_2,
                    'City' => $item->buyer_city,
                    'County' => $item->buyer_county,
                    'Post Code' => $item->buyer_postcode,
                    'Country' => $item->buyer_county,
                    'Buyer Phone Number' => $item->post_to_phone,
                    'Buyer eBay User_ID' => $item->ebay_username,
                ];
            }

            $rBorder = "F";
            $filename = "B2B Buyers";
            $count = count($ebayOrderList) + 1;
            $file = \Maatwebsite\Excel\Facades\Excel::create($filename, function ($excel) use ($ebayOrderList, $count, $rBorder) {
                        $excel->setTitle('Items');
                        $excel->sheet('Items', function ($sheet) use ($ebayOrderList, $count, $rBorder) {
                            $sheet->fromArray($ebayOrderList);
                            $sheet->setFontSize(10);
                            // Left Border
                            $sheet->cells('A1:A' . $count, function ($cells) {
                                $cells->setBorder('none', 'none', 'none', 'none');
                            });
                            // Right Border
                            $sheet->cells($rBorder . '1:' . $rBorder . $count, function ($cells) {
                                $cells->setBorder('none', 'none', 'none', 'none');
                            });
                            // Top+Bottom border - first row
                            $sheet->row(1, function ($row) {
                                $row->setBorder('none', 'none', 'none', 'none');
                                $row->setFontSize(11);
                            });
                            // Bottom border - last row
                            $sheet->row($count, function ($row) {
                                $row->setBorder('none', 'none', 'none', 'none');
                            });
                        });
                    });

            $storagePath = base_path('public/send/');
            $file->store('csv', $storagePath, $returnInfo = true);

            \Illuminate\Support\Facades\Mail::send('emails.ebay.daily-email', [], function (Message $mail) use($storagePath) {
                $mail->subject('New Potential B2B Buyers')
                        ->to(config('mail.toria_address'))
                        ->cc(config('mail.chris_eaton.address'), config('mail.chris_eaton.name'))
                        ->from(config('mail.support_trg'))
                        ->attach($storagePath . 'B2B Buyers.csv');
            });

            File::delete($storagePath . $filename . '.csv');
        }
    }

}
