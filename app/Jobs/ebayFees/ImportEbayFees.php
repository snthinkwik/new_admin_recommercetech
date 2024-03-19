<?php

namespace App\Jobs\ebayFees;

use App\Models\EbayFees;
use App\Models\EbayFeesHistory;
use App\Models\EbayFeesLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\Message;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ImportEbayFees implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */

    protected $dataImported;
    protected $email;
    protected $username;
    protected $fileName;
    public function __construct($dataImported, $email, $username, $fileName)
    {
        $this->dataImported = $dataImported;
        $this->email = $email;
        $this->username = $username;
        $this->fileName = $fileName;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $success = 0;
        $failed = 0;
        if (!empty($this->dataImported) && $this->dataImported->count()) {
            foreach ($this->dataImported->toArray() as $data) {
                $ebayFeeModel = EbayFees::where(
                    [
                        'date' => $data['date'],
                        'item_number' => $data['item'],
                        'fee_type' => $data['fee_type']
                    ]
                )->first();

                if (is_null($ebayFeeModel)) {
                    if (strtolower(trim($data['fee_type'])) !== "payment" && strtolower(trim($data['fee_type'])) !== "subscription fee" && $data['fee_type'] !== "N/A") {
                        $eBayUser = "";

                        if ($data['fee_type'] == "Final Value Fee") {
                            /* $finalString = str_replace(strtok($data['title'], ';'), "", $data['title']);
                              $finalString = substr($finalString, strrpos($finalString, ';') + 1);
                              $eBayUser = strstr($finalString, 'Final price:', true); */

                            if (preg_match('/;(.*?) Final price/', $data['title'], $match) == 1) {
                                $eBayUser = trim($match[1]);
                            } else if (preg_match('/;(.*?) Best Offer price/', $data['title'], $match) == 1) {
                                $eBayUser = trim($match[1]);
                            }
                        } elseif ($data['fee_type'] == "Ad fee") {
                            if (preg_match('/Sold to:(.*?). Sale Price/', $data['title'], $match) == 1) {
                                $eBayUser = trim($match[1]);
                            }
                            /* $finalString = str_replace(strtok($data['title'], ';'), "", $data['title']);
                              $finalString = str_replace(";Sold to: ", "", $finalString);
                              $eBayUser = strtok($finalString, '.'); */
                        }

                        if (empty($data["amount"]) || trim($data["amount"]) == "£0.00" || strpos($data["amount"], '-') !== false)
                            $data['matched'] = "N/A";


                        $data['formatted_fee_date'] = date("Y-m-d H:i:s", strtotime($data['date']));
                        $data['received_top_rated_discount'] = $data['receivedtoprateddiscount'];
                        $data['item_number'] = $data['item'];
                        $data['ebay_username'] = $eBayUser;

                        unset($data['receivedtoprateddiscount']);
                        unset($data['vinserial_number']);
                        unset($data['order_number']);
                        unset($data['item']);

                        EbayFees::insert($data);
                        $success++;

                        $eBayFeeLog = new EbayFeesLog();
                        $eBayFeeLog->fees_id = DB::getPdo()->lastInsertId();
                        $eBayFeeLog->content = "Imported New Fee. Title: " . $data['title'] . "\n";
                        $eBayFeeLog->save();
                    }
                } else
                    $failed++;
            }
        }

        $eBayFeesHistoryModel = new EbayFeesHistory();

        $content = $this->fileName . " file imported at " . date('d/m/Y H:i') . " and imported by " . $this->username;
        $eBayFeesHistoryModel->content = $content;
        $eBayFeesHistoryModel->save();

        Mail::send('emails.ebay-fee.ebay-fee-import', ['success' => $success, 'failed' => $failed], function (Message $mail) {
            $mail->subject('eBay Fees Import Report')
                ->to($this->email)
                ->from(config('mail.help_address'), config('mail.from.name'));
        });
    }
}
