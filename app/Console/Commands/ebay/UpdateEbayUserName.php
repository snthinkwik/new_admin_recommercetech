<?php

namespace App\Console\Commands\ebay;

use App\Models\EbayOrderLog;
use App\Models\EbayOrders;
use Illuminate\Console\Command;

class UpdateEbayUserName extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'ebay:orderhub-update-ebay-user-name';

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
        //	dd("working");
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
    	try {
		    $conn_id = ftp_connect(env('FTP_EBAY_IMPORT_HOST')) or die("Could not connect to ftp server");;

		    ftp_login($conn_id, env('FTP_EBAY_IMPORT_USERNAME'), env('FTP_EBAY_IMPORT_PASSWORD'));
		    ftp_pasv($conn_id, true);

		    $GetFile = ftp_nlist($conn_id, 'test');
		    $CsvFileList = array_values(array_diff($GetFile, array('.', '..', '.DS_Store')));

		    if (count($CsvFileList) > 0) {
			    foreach ($CsvFileList as $file) {
				    $h = fopen('php://temp', 'r+');

				    ftp_fget($conn_id, $h, 'test/' . $file, FTP_BINARY, 0);

				    $fstats = fstat($h);
				    fseek($h, 0);
				    $contents = fread($h, $fstats['size']);
				    $contentsAll = explode("\n", $contents);

				    foreach ($contentsAll as $content) {
					    $getVal = explode(',', $content);
					    if (isset($getVal[0]) && isset($getVal[1])) {
						    if ($getVal[0] !== "Order ID" && $getVal[1] !== "Username") {

							    $eBayOrders = EbayOrders::where('sales_record_number', $getVal[0])
								    ->first();

							    if (!is_null($eBayOrders)) {

								    $oldVal = $eBayOrders->ebay_username;
								    $eBayOrders->ebay_username = $getVal[1];
								    $eBayOrders->save();

								    if ($oldVal !== $getVal[1]) {
									    $changesBayOrder = 'Changed "eBay UserName" from ' . $oldVal . ' to ' . $getVal[1];
									    $ebayOrdersLogModel = new EbayOrderLog();
									    $ebayOrdersLogModel->orders_id = $eBayOrders->id;
									    $ebayOrdersLogModel->content = $changesBayOrder;
									    $ebayOrdersLogModel->save();
								    }
							    }
						    }
					    }
				    }

				    if (ftp_delete($conn_id, 'test/' . $file)) {
					    echo "$file deleted successful\n";
				    } else {
					    echo "could not delete $file\n";
				    }
			    }
		    }
		    ftp_close($conn_id);
	    } catch (\Exception $e) {
    		echo $e->getMessage();
	    }
    }

}
