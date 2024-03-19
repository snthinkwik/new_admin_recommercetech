<?php namespace App\Console\Commands\PhoneCheck;

use App\Models\Mobicode\GsxCheck;
use App\Models\PhoneCheck;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockLog;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;


class ProcessChecks extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'phone-check:process-checks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check if Make, Model, Capacity, IMEI, Network, Does Touch ID Work
	                          in report is same as item data';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $date = \Carbon\Carbon::today()->subDays(2);
        $checks = PhoneCheck::where('status', PhoneCheck::STATUS_NEW)->where('created_at', '>=', $date)->get();

        if(!count($checks)) {
            $this->info("Nothing to Process");
            return;
        }

        $this->info("Checks Found: ".$checks->count());
        $click2Unlock = app('App\Contracts\Click2Unlock');
        foreach($checks as $check) {



            $stock = $check->stock;
            if($stock && !in_array($stock->status,[Stock::STATUS_SOLD,Stock::STATUS_PAID,Stock::STATUS_LOST,Stock::STATUS_RETURNED_TO_SUPPLIER]) ) {
                $this->info("Check: " . $check->id . " | Stock: $stock->id");

                $report = json_decode($check->response);

                $productName=$report->Model.' '.$report->Memory.' '.$report->Color;
                $name=str_replace('+',' Plus',$productName);
                //  $productName=Product::where('product_name',$name)->where('archive','0')-

                $productName=Product::where('slug','!=',$report->ProductCode)->where('slug',$report->ProductCode)->where('archive','0')->first();
                if(!is_null($productName)){
                    if (strpos($productName->slug, ',') !== false) {
                        $eanEx = explode(',', $productName->slug);
                    }elseif (strpos($productName->slug, ' ') !== false) {
                        $eanEx = explode(' ', $productName->slug);
                    }else{
                        $eanEx=$productName->slug;
                    }
                    $product=$productName;

                }else{

                    $product=Product::where('slug','like','%'.$report->ModelNo.'%')->where('archive','0')->first();

                    if(!is_null($product)){
                        if (strpos($product->slug, ',') !== false) {

                            $eanEx = explode(',', $product->slug);
                        }elseif (strpos($product->slug, ' ') !== false) {
                            $eanEx = explode(' ', $product->slug);
                        }else{
                            $eanEx=$product->slug;
                        }
                    }

                }

                if(!$report){
                    $reportList = "Test Status:- " . "Untested in Recomm system and Reports" . "\n";
                    $contains = str_contains($reportList, $stock->notes);
                    if(!$contains){
                        $stock->notes = $reportList;
                    }
                    $stock->test_status=Stock::TEST_STATUS_UNTESTED;
                    $stock->save();


                }
                if(!empty($report->LPN)){
                    $stock->imei=$report->MEID;

                }
                if (isset($report->ResolvedMake) && $report->ResolvedMake && $stock->make != $report->ResolvedMake) {
                    $stock->make = $report->ResolvedMake;
                } elseif (isset($report->Make) && $report->Make && $stock->make != $report->Make) {
                    $stock->make = $report->Make;
                }
                if(is_null($product)){
                    if ($stock->name != $report->Model && $report->Model) {
                        $stock->name = $report->Model;
                    }
                }

                if ($stock->network != $report->Carrier && $report->Carrier) {
                    if (!$stock->network_checks()->where('status', GsxCheck::STATUS_DONE)->count()) {
                        $stock->network = $report->Carrier;
                    }
                }
                if ($stock->capacity != $report->Memory && $report->Memory) {
                    $stock->capacity = $report->Memory;
                }
                if ($stock->colour != $report->Color && $report->Color) {
                    $stock->colour = $report->Color;
                }

                if(isset($report->Grade) && $report->Grade && $report->Grade != $stock->condition) {
                    $stock->condition = $report->Grade;
                }

                if(isset($report->Serial) && $report->Serial && $report->Serial != $stock->serial) {
                    $stock->serial = $report->Serial;
                }



                $failed = ""; // notes
                $reportList='';

                if ($report->Failed) {
                    $failedFormatted = $report->Failed;
                    if (strpos($failedFormatted, 'Headset Port') !== false && strpos($failedFormatted, 'Headset-Right') !== false) {
                        $failedFormatted = str_replace(',Headset-Right', '', $failedFormatted);
                    }
                    if (strpos($failedFormatted, 'Headset Port') !== false && strpos($failedFormatted, 'Headset-Left') !== false) {
                        $failedFormatted = str_replace(',Headset-Left', '', $failedFormatted);
                    }

                    if (strpos(strtolower($report->Model), 'iphone 7') !== false && strpos($failedFormatted, 'Front Microphone')
                        !== false && strpos($failedFormatted, 'Microphone')
                        !== false && strpos($failedFormatted, 'Video Microphone') !== false
                    ) {
                        $failedFormatted = str_replace(',Front Microphone', '', $failedFormatted);
                        $failedFormatted = str_replace('Front Microphone', '', $failedFormatted);
                        $failedFormatted = str_replace(',Microphone', '', $failedFormatted);
                        $failedFormatted = str_replace(',Video Microphone', '', $failedFormatted);
                        $failedFormatted = str_replace('Video Microphone', '', $failedFormatted);
                        $failedFormatted = str_replace('Microphone', '', $failedFormatted);
                        if (strlen($failedFormatted) > 0) {
                            $failedFormatted .= ",Sound IC Issue";
                        } else {
                            $failedFormatted = "Sound IC Issue";
                        }
                    }

                    $failedFormatted = explode(',', $failedFormatted);
                    $this->info($stock->notes);
                    foreach ($failedFormatted as &$f) {
                        //$f = 'Failed ' . $f;
                        $this->comment($f);
                    }
                    $failed = implode(", ", $failedFormatted);
                    $reportList .= "Failed:-" . rtrim($failed,', ') . "\n";


                }

//				if(!$stock->manual_notes) { #1649
//					$stock->notes = $failed;
//				}


                if (strpos($report->Passed, 'LCD') !== false && strpos($report->Passed, 'Glass Cracked') !== false) {
                    $this->info('good lcd, good glass');
                    $stock->lcd_status = Stock::LCD_GOOD_GLASS_GOOD;
                } elseif (strpos($report->Passed, 'LCD') !== false && strpos($report->Failed, 'Glass Cracked') !== false) {
                    $this->info('good lcd, broken glass');
                    $stock->lcd_status = Stock::LCD_GOOD_GLASS_BAD;
                } elseif (strpos($report->Failed, 'LCD') !== false && strpos($report->Passed, 'Glass Cracked') !== false) {
                    $this->info('bad lcd, good glass');
                    $stock->lcd_status = Stock::LCD_BAD_GLASS_GOOD;
                } elseif (strpos($report->Failed, 'LCD') !== false && strpos($report->Failed, 'Glass Cracked') !== false) {
                    $this->info('bad lcd, broken glass');
                    $stock->lcd_status = Stock::LCD_BAD_GLASS_BAD;
                }


                if (strpos($report->Passed, 'Fingerprint Sensor') !== false && $stock->touch_id_working != Stock::TOUCH_ID_WORKING_YES) {
                    $stock->touch_id_working = Stock::TOUCH_ID_WORKING_YES;
                } elseif (strpos($report->Failed, 'Fingerprint Sensor') !== false && $stock->touch_id_working != Stock::TOUCH_ID_WORKING_NO) {
                    $stock->touch_id_working = Stock::TOUCH_ID_WORKING_NO;
                } elseif (strpos($report->Passed, 'Fingerprint Sensor') === false && strpos($report->Failed, 'Fingerprint Sensor') === false && $stock->touch_id_working != Stock::TOUCH_ID_WORKING_NA) {
                    $stock->touch_id_working = Stock::TOUCH_ID_WORKING_NA;
                }

                if (strpos($report->Passed, 'Face ID') !== false && $stock->touch_id_working != Stock::TOUCH_ID_WORKING_YES) {
                    $stock->touch_id_working = Stock::TOUCH_ID_WORKING_YES;
                } elseif (strpos($report->Failed, 'Face ID') !== false && $stock->touch_id_working != Stock::TOUCH_ID_WORKING_NO) {
                    $stock->touch_id_working = Stock::TOUCH_ID_WORKING_NO;
                } elseif (strpos($report->Passed, 'Face ID') === false && strpos($report->Failed, 'Face ID') === false && $stock->touch_id_working != Stock::TOUCH_ID_UNSURE) {

                    $tyList=[];
                    if(isset($report->Parts) && isset(json_decode($report->Parts)->Data))
                    {

                        foreach (json_decode($report->Parts)->Data as $data){
                            array_push($tyList,$data->name);
                        }

                    }

                    if(in_array('Touch ID',$tyList)){
                        $stock->touch_id_working = Stock::TOUCH_ID_WORKING_YES;
                    }else{
                        $stock->touch_id_working = Stock::TOUCH_ID_UNSURE;

                    }

                }
                $this->info("Failed: " . $report->Failed);
                $this->question("Battery Percentage: " . $report->BatteryHealthPercentage);
                if(strtolower($report->OS) == 'android') {
                    $this->question("Android");
                    if ($stock->lcd_status == Stock::LCD_GOOD_GLASS_GOOD && !$report->Failed && in_array($stock->touch_id_working, [Stock::TOUCH_ID_WORKING_YES, Stock::TOUCH_ID_WORKING_NA])) {
                        $this->info("Fully Working");
                        $stock->grade = Stock::GRADE_FULLY_WORKING;
                    } elseif ($stock->lcd_status == Stock::LCD_GOOD_GLASS_GOOD && !$report->Failed && in_array($stock->touch_id_working, [Stock::TOUCH_ID_WORKING_NO])) {
                        $this->info("Fully Working - No Touch ID");
                        $stock->grade = Stock::GRADE_FULLY_WORKING_NO_TOUCH_ID;
                    } elseif (strpos($report->Failed, 'Fingerprint Sensor') !== false && isset($failedFormatted) && count($failedFormatted) == 1) {
                        $this->info("Fully Working - No Touch ID - only Fingerprint Sensor is Failed");
                        $stock->grade = Stock::GRADE_FULLY_WORKING_NO_TOUCH_ID;
                    } elseif (strpos($report->Failed, 'Fingerprint Sensor') !== false && isset($failedFormatted) && count($failedFormatted) > 1) {
                        $this->info('Major Fault - Fingerprint Sensor and other faults');
                        $stock->grade = Stock::GRADE_MAJOR_FAULT;
                    } elseif (
                        ($report->Failed && strpos($failed, 'Sound IC Issue') !== false) ||
                        ($report->Failed && strpos($failed, 'Network Connectivity') !== false) ||
                        ($report->Failed && strpos($failed, 'Sim Reader') !== false)
                    ) {
                        $this->info('Major Fault');
                        $stock->grade = Stock::GRADE_MAJOR_FAULT;
                    } elseif ($report->Failed) {
                        $this->info("Minor Fault");
                        $stock->grade = Stock::GRADE_MINOR_FAULT;
                    }
                } else {
                    if ($stock->lcd_status == Stock::LCD_GOOD_GLASS_GOOD && !$report->Failed && $report->BatteryHealthPercentage > 80 && in_array($stock->touch_id_working, [Stock::TOUCH_ID_WORKING_YES, Stock::TOUCH_ID_WORKING_NA])) {
                        $this->info("Fully Working");
                        $stock->grade = Stock::GRADE_FULLY_WORKING;
                    } elseif ($stock->lcd_status == Stock::LCD_GOOD_GLASS_GOOD && !$report->Failed && $report->BatteryHealthPercentage > 80 && in_array($stock->touch_id_working, [Stock::TOUCH_ID_WORKING_NO])) {
                        $this->info("Fully Working - No Touch ID");
                        $stock->grade = Stock::GRADE_FULLY_WORKING_NO_TOUCH_ID;
                    } elseif (strpos($report->Failed, 'Fingerprint Sensor') !== false && isset($failedFormatted) && count($failedFormatted) == 1) {
                        $this->info("Fully Working - No Touch ID - only Fingerprint Sensor is Failed");
                        $stock->grade = Stock::GRADE_FULLY_WORKING_NO_TOUCH_ID;
                    } elseif (strpos($report->Failed, 'Fingerprint Sensor') !== false && isset($failedFormatted) && count($failedFormatted) > 1) {
                        $this->info('Major Fault - Fingerprint Sensor and other faults');
                        $stock->grade = Stock::GRADE_MAJOR_FAULT;
                    }elseif(strpos($report->Failed, 'Face ID') !== false && isset($failedFormatted) && count($failedFormatted) > 1){
                        $this->info('Major Fault - Face ID and other faults');
                        $stock->grade = Stock::GRADE_MAJOR_FAULT;
                    }
                    elseif (
                        ($report->Failed && strpos($failed, 'Sound IC Issue') !== false) ||
                        ($report->Failed && strpos($failed, 'Network Connectivity') !== false) ||
                        ($report->Failed && strpos($failed, 'Sim Reader') !== false)
                    ) {
                        $this->info('Major Fault');
                        $stock->grade = Stock::GRADE_MAJOR_FAULT;
                    } elseif ($report->Failed) {
                        $this->info("Minor Fault");
                        $stock->grade = Stock::GRADE_MINOR_FAULT;
                    }
                }

                if($report->Working === "Yes" && empty($reportList) ){
                    $stock->grade = Stock::GRADE_FULLY_WORKING;
                    if(in_array($stock->condition,[Stock::CONDITION_A,Stock::CONDITION_B,Stock::CONDITION_C])){
                        if(!in_array($stock->status,[Stock::STATUS_SOLD,Stock::STATUS_PAID,Stock::STATUS_LOST,Stock::STATUS_RETURNED_TO_SUPPLIER])){


                            if($stock->network===Stock::NETWORK_CHECK_UNLOCKED){
                                $stock->status=Stock::STATUS_RETAIL_STOCK;
                            }

                        }
                    }
                }else{
                    if(in_array($stock->condition,[Stock::CONDITION_A,Stock::CONDITION_B,Stock::CONDITION_C])){
                        if(!in_array($stock->status,[Stock::STATUS_SOLD,Stock::STATUS_PAID,Stock::STATUS_LOST,Stock::STATUS_RETURNED_TO_SUPPLIER])){
                            $stock->status=Stock::STATUS_READY_FOR_SALE;
                        }

                    }
                }


                if($report->DeviceLock==="On"){
                    $stock->grade = Stock::GRADE_LOCKED;
                }
//                $blacklist=['Bad','Bad/OB','Bad/UB'];
//
//                if(in_array($report->ESN,$blacklist)){
//                    $stock->grade = Stock::GRADE_BLACKLISTED;
//                }

                $black=$click2Unlock->getBlackListed($stock->imei);

                if(!is_null($black)){
                    if($black->status!=="error"){
                        if($black->data->report_status==="success"){

                            $sub = substr($black->data->report, strpos($black->data->report,'Blacklist Status:')+strlen('Blacklist Status:'),strlen($black->data->report));
                            if(trim(strip_tags(substr($sub,0,strpos($sub,'Country:'))))==="blocked" || trim(strip_tags(substr($sub,0,strpos($sub,'Country:'))))==="BLOCKED"){
                                $stock->grade = Stock::GRADE_BLACKLISTED;
                            }
                        }
                    }
                }


                if($report->Working === "Yes"){

                    $reportList .= "Test Status:- " . "Tested - Fully Working" . "\n";
                    $contains = str_contains($reportList, $stock->notes);

                    if(!$contains){
                        $stock->notes = $reportList;
                    }


                    if(!is_null($product)){

                        if($product->non_serialised){
                            $stock->non_serialised=1;

                        }
                        $stock->product_id=$product->id;
                        if(is_array($eanEx)){
                            if(in_array($report->ModelNo,$eanEx)){
                                $stock->mpn_map=1;
                            }else{
                                $stock->mpn_map=0;
                            }

                        }else{
                            if($report->ModelNo === $eanEx){
                                $stock->mpn_map=1;
                            }else{
                                $stock->mpn_map=0;
                            }
                        }

                        $stockName=str_replace('"','',$stock->name);


                        $stockFullName=$stockName.' '.$stock->capacity_formatted.' '.$stock->colour;
                        $this->info($stockFullName);
                        if($stockFullName !==$product->product_name){

                            $trimSlug=  str_replace( array('GB'), '@rt', $product->product_name);
                            $stock->name=$trimSlug;
                            $stock->name_compare=1;
                            $this->info("Stock Name Update");

                        }
                    }

                    if(is_null($stock->colour)){
                        $stock->colour=$report->Color;
                    }
                    $stock->test_status=Stock::TEST_STATUS_COMPLETE;

                }
                if($report->Working==='Pending'){

                    $reportList .= "Test Status:-" . "Recomm system as Pending - Test not completed" . "\n";
                    $contains = str_contains($reportList, $stock->notes);
                    if(!$contains){
                        $stock->notes = $reportList;
                    }

                    $stock->test_status=Stock::TEST_STATUS_PENDING;

                }
                if($report->Working ==="No"){


                    $stock->test_status=Stock::TEST_STATUS_COMPLETE;
                    $stock->notes = $reportList;
                    if(!is_null($product)){
                        if($product->non_serialised){
                            $stock->non_serialised=1;

                        }
                        $stock->product_id=$product->id;
                        if(is_array($eanEx)){
                            if(in_array($report->ModelNo,$eanEx)){
                                $stock->mpn_map=1;
                            }else{
                                $stock->mpn_map=0;
                            }

                        }else{
                            if($report->ModelNo === $eanEx){
                                $stock->mpn_map=1;
                            }else{
                                $stock->mpn_map=0;
                            }
                        }


                        $stockName=str_replace('"','',$stock->name);



                        $stockFullName=$stockName.' '.$stock->capacity_formatted.' '.$stock->colour;
                        $this->info($stockFullName);

                        if($stockFullName !== $product->product_name){
                            $trimSlug=  str_replace( array('GB'), '@rt', $product->product_name);

                            $stock->name=$trimSlug;
                            $stock->name_compare=1;
                            $this->info("Stock Name Update");

                        }



                    }
                    if(is_null($stock->colour)){
                        $stock->colour=$report->Color;
                    }

                }



                $oemList=[];


                if(!is_null($report->Parts)){

                    if(isset(json_decode($report->Parts)->Data)){
                        foreach (json_decode($report->Parts)->Data as $part){
                            if(isset($part->currentCheckSum)){
                                if($part->currentCheckSum !=="null"){
                                    $oemList[]=[
                                        'name'=>$part->name,
                                        'currentCheckSum'=> isset($part->currentCheckSum) ?$part->currentCheckSum:'',
                                        'status'=>isset($part->Status)?$part->Status:'',
                                        'factorySerial'=>isset($part->FactorySerial)?$part->FactorySerial:'',
                                        'checkSum'=>isset($part->checkSum)?$part->checkSum:'',
                                        'notice'=>isset($part->notice) ? $part->notice:'',
                                        'currentSerial'=>isset($part->CurrentSerial)?$part->CurrentSerial:'',
                                    ];
                                }
                            }
                        }
                    }

                }

                if(count($oemList)>0){
                    $stock->oem_parts=json_encode($oemList);
                }

                if($report->Cosmetics!==""){

                    $reportList .= "Cosmetics:-".$report->Cosmetics."\n";

                    if($reportList){
                        $stock->notes = $reportList;
                    }
                    $stock->cosmetic_type=$report->Cosmetics;

                    if(strpos($report->Cosmetics, 'cracked back glass') !== false){

                        //$word=explode(',',$report->Cosmetics);

                        $cosmeticList=explode(',',$report->Cosmetics);
                        $crackedBackList=[];

                        foreach ($cosmeticList as $cosmetic){

                            if (strpos($cosmetic, 'cracked back glass') !== false) {

                                $getString=   substr($cosmetic, strpos($cosmetic, "cracked back glass")  -6);
                                $word=explode(' ',$getString);
                                array_push($crackedBackList,$word[0]);

                            }


                            if (strpos($cosmetic, 'Laser Engraved') !== false) {
                                $getString=   substr($cosmetic, strpos($cosmetic, "Laser Engraved") ) ;
                                $word=explode(' ',$getString);
                                array_push($crackedBackList,'Lase');

                            }

                        }
                        $stock->cracked_back= count($crackedBackList)>0 ? implode(",",$crackedBackList):'';
                    }else{
                        $stock->cracked_back="No";
                    }


                }
                if ($stock->isDirty()) {

                    $changes = "";
                    foreach ($stock->getAttributes() as $key => $value) {
                        if ($value !== $stock->getOriginal($key) && !checkUpdatedFields($value, $stock->getOriginal($key))) {
                            $orgVal = $stock->getOriginal($key);

                            $changes .= "Changed \"$key\" from \"$orgVal\" to \"$value\".\n";
                        }
                    }
                    if ($changes) {
                        $this->comment($changes);
                        StockLog::create([
                            'stock_id' => $stock->id,
                            'content' => "Process Phone Check:\n " . $changes
                        ]);
                    }
                    $options = ['phone_check_save' => 1];
                    $stock->save($options);
                }

                $stock->phone_check_create_at=$report->DeviceCreatedDate;
                $stock->save();



                $check->status = PhoneCheck::STATUS_DONE;
                $check->save();
            }
        }
    }

}
