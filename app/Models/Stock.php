<?php

namespace App\Models;

use App\Colour;
use App\Csv\Parser;
use App\Http\Requests\StockItemRequest;
use App\Mobicode\GsxCheck;
use App\Network;
use App\PhoneCheck;
use App\Product;
use App\RepairsItems;
use App\Sku;
use App\StockLog;
use App\Supplier;
use App\Unlock;
use App\UnlockMapping;
use App\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\File\File;

class Stock extends Model
{
    use HasFactory;
    const SHOWN_TO_NONE = 'None';
    const SHOWN_TO_ALL = 'TRG Site';
    const SHOWN_TO_EBAY = 'OrderHub';
    const SHOWN_TO_EBAY_AND_SHOP = 'eBay & Shop';
    const SHOWN_TO_EBAY_AUCTION = 'eBay Auction';
    const SHOWN_TO_AMAZON_FBA = 'Amazon FBA';
    const STATUS_IN_STOCK = 'In Stock';
    const STATUS_RE_TEST = 'Re-test';
    const STATUS_SOLD = 'Sold';
    const STATUS_PAID = 'Paid';
    const STATUS_INBOUND = 'Inbound';
    const STATUS_BATCH = 'Batch';
    const STATUS_REPAIR = 'In Repair';
    const STATUS_RETURNED_TO_SUPPLIER = "Returned to Supplier";
    const STATUS_LOST = "Lost";
    const STATUS_3RD_PARTY = "3rd Party";
    const STATUS_DELETED = "Deleted";
    const STATUS_READY_FOR_SALE = "Ready for Sale";
    const STATUS_RETAIL_STOCK = "Retail Stock";
    const STATUS_LISTED_ON_AUCTION = "Listed on Auction";
    const STATUS_RESERVED_FOR_ORDER = "Reserved for Order";
    const STATUS_EBAY_LISTED='Listed on eBay';
    const STATUS_ALLOCATED='Allocated';



    //platform
    const PLATFROM_EBAY='eBay';
    const PLATFROM_BACKMARCKET='Back Market';
    const PLATFROM_RECOMM='Recomm';
    const PLATFROM_MOBILE_ADVANTAGE='Mobile Advantage';




    const GRADE_FULLY_WORKING_NO_TOUCH_ID = "Fully Working - No Touch ID";
    const GRADE_FULLY_WORKING_NO_FACE_ID = "Fully Working - No Face ID";
    const GRADE_FULLY_WORKING = 'Fully Working';
    const GRADE_MINOR_FAULT = 'Minor Fault';
    const GRADE_MAJOR_FAULT = 'Major Fault';
    const GRADE_BROKEN = 'No Signs of Life';
    const GRADE_LOCKED = 'iCloud Locked';
    const GRADE_LOCKED_CLEAN = 'iCloud Locked - Clean';
    const GRADE_LOCKED_LOST = 'iCloud Locked - Lost';
    const GRADE_SHOP_GRADE = "Shop Grade";
    const GRADE_NEW = "New";
    const GRADE_BLACKLISTED ='Blacklisted';
    // below are Samsung only grades
    const GRADE_WARRANTY_REPAIR = 'Warranty Repair';
    const GRADE_FULLY_WORKING_CRACKED_GLASS = 'Fully Working - Cracked Glass';
    const GRADE_FULLY_WORKING_CRACKED_BACK = 'Fully Working - Cracked Back';
    const GRADE_FULLY_WORKING_NO_FINGERPRINT_SENSOR = 'Fully Working - No Fingerprint Sensor';
    const GRADE_AWAITING_BATTERY_PART = 'Awaiting Battery / Part';
    const GRADE_BAD_LCD_SLIGHT_BLEMISH = 'Bad LCD - Slight Blemish';
    const GRADE_BAD_LCD_FULLY_BROKEN = 'Bad LCD - Fully Broken';
    const CONDITION_A = 'A';
    const CONDITION_B = 'B';
    const CONDITION_C = 'C';
    const CONDITION_D = 'D';
    const CONDITION_E = 'E';
    const LCD_UNSPECIFIED = '';
    const LCD_GOOD_GLASS_GOOD = 'Good LCD - Good Glass';
    const LCD_GOOD_GLASS_BAD = 'Good LCD - Broken Glass';
    const LCD_BAD_GLASS_BAD = 'Bad LCD - Broken Glass';
    const LCD_BAD_GLASS_GOOD = 'Bad LCD - Good Glass';
    const LCD_GOOD_AND_GLASS_SLIGHT_BLEMISH = 'Good LCD & Glass - Slight Blemish';
    const TOUCH_ID_WORKING_YES = 'Yes';
    const TOUCH_ID_WORKING_NO = 'No';
    const TOUCH_ID_WORKING_NA = 'N/A';
    const TOUCH_ID_UNSURE='Unsure';
    const FAULT_CHARGING_PORT = "Charging Port";
    const FAULT_FRONT_CAMERA = "Front Camera";
    const FAULT_REAR_CAMERA = "Rear Camera";
    const FAULT_VOLUME_BUTTONS = "Volume Buttons";
    const FAULT_POWER_BUTTON = "Power Button";
    const FAULT_HEADPHONE_JACK = "Headphone Jack";
    const FAULT_LOUD_SPEAKER = "Loud Speaker";
    const FAULT_PROXIMITY_SENSOR = "Proximity Sensor";
    const FAULT_EAR_SPEAKER = "Ear Speaker";
    const FAULT_MICROPHONE = "Microphone";
    const FAULT_FLASH = "Flash";
    const FAULT_TORCH = "Torch";
    const FAULT_VIBRATION = "Vibration";
    const FAULT_BROKEN_SCREEN = "Broken Screen";
    const FAULT_BATTERY = "Battery";
    const FAULT_MUTE_SWITCH = "Mute Switch";
    const SOLD_AS_EBAY = "eBay";
    const SOLD_AS_WHOLESALE_MINOR_FAULTS = "Wholesale Minor Faults";
    const SOLD_AS_WHOLESALE_FULLY_WORKING = "Wholesale Fully Working";
    const SOLD_AS_REFURBISH_AND_SELL = "Refurbish and Sell";
    const SOLD_AS_REPAIR_AND_SELL = "Repair and Sell";
    const PURCHASE_COUNTRY_UK = "UK";
    const PURCHASE_COUNTRY_US = "US";
    const LOST_REASON_LOST_BY_COURIER = "Lost by courier";
    const LOST_REASON_LOST_AT_TRG = "Lost at Recommercetech";
    const LOST_REASON_REPORTED_LOST_BY_CUSTOMER = "Reported lost by customer";

    const PRODUCT_TYPE_MOBILE_PHONE = "Mobile Phone";
    const PRODUCT_TYPE_TABLET = "Tablet";
    const PRODUCT_TYPE_DESKTOP = "Desktop";
    const PRODUCT_TYPE_COMPUTER = "Computer";
    const PRODUCT_TYPE_ACCESSORIES = "Accessories";
    const CRACKED_BACK_YES = "Yes";
    const CRACKED_BACK_No = "No";
    const TEST_STATUS_COMPLETE = "Complete";
    const TEST_STATUS_PENDING = "Pending";
    const TEST_STATUS_UNTESTED = "Untested";
    const VAT_TYPE_STD='Standard';
    const VAT_TYPE_MAG='Margin';
    const NETWORK_SIM_LOCKERD='Sim Locked';
    const NETWORK_CHECK_NOT_REQ='Check Not Req';
    const NETWORK_CHECK_UNLOCKED='Unlocked';
    const NETWORK_NO_RESULT='No Result';


    /**
     * @var array Important note: Vodafone can't be added here without considering a few things. We use the command
     * imeis:vodafone-special-unlocks to unlock _some_ Vodafone devices without extra costs. So if Vodafone devices
     * were to be unlocked for free, whether it were all of them or just the ones that don't cost us anything, we'd
     * have to make sure that we don't post devices that we can unlock for free to the paid service. Most likely we'd
     * need to modify App\Observers\StockObserver so that it uses a new property $item->network_for_unlock instead of
     * $item->network. network_for_unlock would be almost the same as the regular network, only it'd be different for
     * variants of the same network that can be unlocked in different ways. Of course that's just a suggestion, if you
     * actually have to do it, you might come up with another solution. Just keep in mind that currently it's consistent
     * because Vodafone is not included below and is handled entirely separately.
     *
     * So what about Vodafone below (in self::$unlockableAdmin)? That's used for admin unlocks not connected to stock
     * items, so there's no conflict.
     */
    protected static $unlockableFree = [/* 'EE', 'O2', 'Three', 'AT&T', 'EMEA', */
        'Vodafone' /* 'Unknown' */];

    /**
     * @var array
     */
    protected static $unlockableAdmin = ['EE', 'O2', 'Three', 'AT&T', 'EMEA', 'Vodafone', 'Unknown', 'EE Corporate'];
    protected $table = 'new_stock';
    protected $dates = ['purchase_date', 'first_unbrick_date', 'marked_as_lost'];
    protected $logChanges = true;
    protected $fillable = [
        'serial', 'sku', 'third_party_ref', 'name', 'make', 'capacity', 'colour', 'grade', 'product_type', 'network', 'purchase_date',
        'purchase_price', 'sale_price', 'sold_at', 'location', 'trg_ref', 'notes', 'condition', 'imei', 'lcd_status',
        'touch_id_working', 'purchase_order_number', 'vendor_name', 'shown_to', 'trg_product_number', 'product_id', 'vat_type', 'cracked_back','total_price_ex_vat',
        'original_grade','original_condition','ps_model'];
    protected $casts = ['sale_price' => 'float', 'purchase_price' => 'float', 'faults' => 'array'];
    protected static $skuAttributes = ['name', 'colour', 'capacity', 'network', 'grade'];
    protected static $availableStatuses = null;
    protected static $availableGrades = null;
    protected static $availableNetworks = null;
    protected $_return_rejection_reason = null;

    public function scopeFromRequest(Builder $query, Request $request, $type = 'main')
    {


        $query = self::query();


        if($request->overview==="true"){

            $query->join('products','products.id','=','new_stock.product_id');
            // $query->whereNotIn('status', [self::STATUS_LOST,self::STATUS_PAID,self::STATUS_SOLD,self::STATUS_DELETED]);
            $query->whereIn('status', ['In Stock',
                'Inbound',
                'Re-test',
                'Batch',
                'In Repair',
                //  'Returned to Supplier',
                '3rd Party',
                'Ready for Sale',
                'Retail Stock',
                'Listed on Auction',
                'Reserved for Order',
                //  'Listed on eBay',
                'Allocated']);
        }



        //$term=  str_replace(array('(', ')','-'), array('', '',''), $request->term);



        if($type !== 'overview'){
            if($request->unsold){
                $query->whereNotIn('status',[self::STATUS_SOLD,self::STATUS_PAID,self::STATUS_DELETED,self::STATUS_RETURNED_TO_SUPPLIER,self::STATUS_DELETED,self::STATUS_LOST]);
            }else{
                $query->whereIn('status',[self::STATUS_SOLD,self::STATUS_PAID,self::STATUS_DELETED,self::STATUS_RETURNED_TO_SUPPLIER,self::STATUS_DELETED,self::STATUS_LOST]);
            }
        }



        if($type === 'overview'){


            if($request->status){
                $query->where('status', $request->status);
            }

            if($request->capacity){
                $query->where('new_stock.capacity', $request->capacity);
            }

            if($request->colour) {
                $query->where('new_stock.colour', $request->colour);
            }

            if($request->term){
                $tokens = preg_split('/\s+(?!\d)/', $request->term);
                foreach ($tokens as $token) {
                    $query->where(function ($subWhere) use ($token) {
                        foreach (['new_stock.id', 'new_stock.name', 'new_stock.network', 'new_stock.colour', 'new_stock.serial', 'new_stock.imei', 'new_stock.third_party_ref', 'new_stock.sku'] as $field) {
                            if ($field === 'id' && strtolower(substr($token, 0, 3)) === 'rct') {
                                $subWhere->orWhere($field, 'like', "%" . substr($token, 3) . "%");
                            }
                            else {
                                $subWhere->orWhere($field,'REGEXP','[[:<:]]'.$token.'[[:>:]]');
                            }
                        }
                    });
                    if (strlen($token) === 15) {
                        $query->orWhere('sku', $token);
                    }
                }
            }
        }

        if($type !== 'overview') {
            $query->query($request->term);
        }
        $query->grade($request->grade);
        $query->network($request->network);
        $query->condition($request->condition);


        if($type !== 'overview'){
            $query->colour($request->colour);
        }

        if($type !== 'overview'){
            $query->capacity($request->capacity);
        }




        $query->touchIdWorking($request->touch_id_working);
        $query->crackedBack($request->cracked_back);
        $query->productType($request->product_type);
        $query->testStatus($request->test_status);
        $query->mpnMapping($request->mpa_map);




        if ($request->vat_type) {
            $query->where('vat_type', $request->vat_type);
        }
        if ($request->purchase_country) {
            $query->where('purchase_country', $request->purchase_country);
        }
        if ($request->sort)
            $query->orderBy($request->sort, $request->sortO);
        elseif ($type != 'overview')
            $query->orderBy('id', 'desc');

        if ($type === 'main') {
            $query->with('sale');
            $query->status($request->status);
            if ($request->warranty && Auth::user()->type === 'admin') {
                $query->where("status", self::STATUS_IN_STOCK);
                $query->where("first_unbrick_at", ">=", \Carbon\Carbon::now()->subYear());
                $query->where("show_warranty", true);
            }
            if ($request->aged_stock && Auth::user()->type === 'admin') {
                $query->where("created_at", "<", \Carbon\Carbon::now()->subDays(7)->startOfDay());
            }
            if ($request->shown_to && Auth::user()->type === 'admin') {
                if ($request->shown_to == 'All')
                    $query->where("shown_to", self::SHOWN_TO_ALL);
                elseif ($request->shown_to == 'eBay')
                    $query->where("shown_to", self::SHOWN_TO_EBAY);
                elseif ($request->shown_to == 'eBay_ePos')
                    $query->whereIn('shown_to', [self::SHOWN_TO_EBAY, self::SHOWN_TO_EBAY_AND_SHOP]);
                elseif ($request->shown_to == 'Amazon FBA')
                    $query->where("shown_to", self::SHOWN_TO_AMAZON_FBA);
            } elseif ($request->shown_to) {
                if ($request->shown_to == 'None')
                    $query->whereIn('shown_to', [self::SHOWN_TO_NONE]);
                else
                    $query->whereIn('shown_to', [self::SHOWN_TO_ALL, self::SHOWN_TO_NONE]);
                $query->whereIn("status", [self::STATUS_INBOUND, self::STATUS_IN_STOCK]);
            } else {
                $query->shownToUser(Auth::user());
            }

            if ($request->listed) {
                $query->where('listed', $request->listed);
            }

            if (!Auth::user()->canRead('stock.all')) {
                $query->whereIn('status', Auth::user()->allowed_statuses_viewing)->where('sale_price', '>', 0);
            }

            if (Auth::user()->type === 'admin' && $request->neg_margin) {
                $query->where('sale_price', '<', \DB::raw('purchase_price'));
            }

            if (Auth::user()->type === 'admin' && $request->has('product_mapping')) {
                if ($request->product_mapping)
                    $query->whereNotNull('product_id');
                elseif ($request->has('product_mapping'))
                    $query->whereNull('product_id');
            }



            if($request->cosmetic_fault_type){

                $query->where('cosmetic_type', 'like', "%$request->cosmetic_fault_type%");
                //$query->whereRaw('JSON_CONTAINS("cosmetic_type", "'.$request->cosmetic_fault_type.'")');
            }




        }

        if ($type === 'overview') {


            if($request->cosmetic_fault_type){
                $query->where('cosmetic_type', 'like', "%$request->cosmetic_fault_type%");
            }
            $query->skuOverview(
                [   self::STATUS_IN_STOCK,
                    self::STATUS_INBOUND,
                    self::STATUS_RE_TEST,
                    self::STATUS_BATCH,
                    self::STATUS_REPAIR,
                    //self::STATUS_RETURNED_TO_SUPPLIER,
                    self::STATUS_3RD_PARTY,
                    self::STATUS_READY_FOR_SALE,
                    self::STATUS_RETAIL_STOCK,
                    self::STATUS_LISTED_ON_AUCTION,
                    self::STATUS_RESERVED_FOR_ORDER,
                    //  self::STATUS_EBAY_LISTED,
                    self::STATUS_ALLOCATED,


                ]);
        }

        return $query;
    }

    public static function getAdminUnlockableNetworks()
    {
        return self::$unlockableAdmin;
    }

    public static function getPreOrderableData()
    {
        $names = [
            'iPhone' => [
                'iPhone SE',
                'iPhone 4',
                'iPhone 4S',
                'iPhone 5',
                'iPhone 5C',
                'iPhone 5S',
                'iPhone 6',
                'iPhone 6 Plus',
                'iPhone 6S',
                'iPhone 6S Plus',
                'iPhone 7',
            ],
            'Samsung Galaxy' => [
                'Galaxy S3',
                'Galaxy S3 Mini',
                'Galaxy S4',
                'Galaxy S4 Mini',
                'Galaxy S5',
                'Galaxy S5 Mini',
                'Galaxy S6',
                'Galaxy S6 Edge',
                'Galaxy S7',
                'Galaxy S7 Edge',
            ],
            'iPad' => [
                'iPad 1',
                'iPad 2',
                'iPad 3',
                'iPad 4',
                'iPad Mini 1',
                'iPad Mini 2',
                'iPad Air',
                'iPad Air 2',
            ]
        ];
        $colours = ['Silver', 'Black', 'Gold', 'Space Grey', 'White'];
        $capacities = ['A', 'B', 'C'];
        $grades = ['Fully Working', 'Minor Fault', 'Major Fault', 'No Signs of Life', 'iCloud Locked'];
        $networks = ['EE', 'O2', 'Orange', 'T-Mobile', 'Three', 'Virgin', 'Vodafone', 'Not Applicable', 'Other', 'Unknown', 'Unlocked'];

        $namesForSelect = [];
        foreach ($names as $group => $groupNames) {
            $namesForSelect[$group] = array_combine($groupNames, $groupNames);
        }

        return new Collection([
            'names' => $namesForSelect,
            'capacities' => [
                '8' => '8GB',
                '16' => '16GB',
                '32' => '32GB',
                '64' => '64GB',
                '128' => '128GB',
                'Any' => 'Any',
            ],
            'colours' => array_combine($colours, $colours) + ['Any' => 'Any'],
            'conditions' => array_combine($capacities, $capacities) + ['Any' => 'Any'],
            'grades' => array_combine($grades, $grades),
            'networks' => array_combine($networks, $networks),
        ]);
    }

    public static function getGsmFusionNetworkMapping()
    {
        return [
            'Unlock' => 'Unlocked',
            'Unlock Service' => 'Unlocked',
            'Retail Unlock' => 'Unlocked',
            'UK Vodafone' => 'Vodafone',
            'Vodafone - United Kingdom' => 'Vodafone',
            'Orange' => 'EE',
            'T-MOBILE' => 'EE',
            'Hutchinson' => 'Three',
            'O2' => 'O2',
            'UK O2 Tesco' => 'O2',
            'EMEA' => 'EMEA',
            'EMEA Service' => 'EMEA',
            'UK Hutchison' => 'Three',
            'UK TMobile Orange' => 'EE',
            'France SFR' => 'Foreign Network',
            'UK Virgin Mobile' => 'EE',
            'UK T-Mobile Orange' => 'EE',
            'UK T-Mobile Orange Policy' => 'EE',
            'UK Hutchison Unlocked' => 'Unlocked',
            'Hong Kong Smartone Unlocked' => 'Unlocked',
            'Ireland Hutchison/O2 Locked' => 'Foreign Network',
            'Norway Telenor Unlocked' => 'Unlocked',
            'Singapore Reseller Unlocked' => 'Unlocked',
            'US Verizon LTE Unlocked' => 'Unlocked',
            'Global BrightStar Unlock Activation' => 'Unlocked',
            'Multi-Mode Unlock' => 'Unlocked',
            'UK Virgin Mobile Unlocked' => 'Unlocked',
            'Netherlands Vodafone' => 'Foreign Network',
            'US AT&T Reseller' => 'AT&T',
            'UK Carphone Flex activation policy' => 'Locks to First Sim',
            'UK Reseller Flex Policy' => 'Locks to First Sim',
            'Denmark Hutchison' => 'Foreign Network',
        ];
    }

    public static function getColourMapping()
    {
        return [
            'Red' => 'Red',
            'Black' => 'Black',
            'Grey' => 'Grey',
            'White' => 'White',
            'Aqua' => 'Aqua',
            'Blue' => 'Blue',
            'Brown' => 'Brown',
            'Green' => 'Green',
            'Gray' => 'Gray',
            'Lime' => 'Lime',
            'Maroon' => 'Maroon',
            'Orange' => 'Orange',
            'Pink' => 'Pink',
            'Purple' => 'Purple',
            'Silver' => 'Silver',
            'Violet' => 'Violet',
            'Yellow' => 'Yellow',
            'Mixed' => 'Mixed',
            'Gold' => 'Gold',
            'Space Grey' => 'Space Grey',
            'Space grey' => 'Space Grey',
            'Space gray' => 'Space Grey',
            'Space Gray' => 'Space Grey',
            'Rose Gold' => 'Rose Gold',
            'Rose gold' => 'Rose Gold',
            'RGLD' => 'Rose Gold',
            'PNK' => 'Pink',
            'SLVR' => 'Silver'
        ];
    }

    public static function parseValidateCsv(File $csv, $salesPriceRequired = false)
    {
        $csvParser = new Parser($csv->getRealPath(), [
            'headerFilter' => function ($columnName) {
                $columnName = strtolower($columnName);
                $columnName = preg_replace('/\W+/', '_', $columnName);
                return $columnName;
            },
            'valueRules' => [
                'purchase_price' => 'amount',
                'sale_price' => 'amount',
                'capacity' => 'device-capacity',
            ],
        ]);

        $rows = $csvParser->getAllRows();
        $errors = [];

        foreach ($rows as $i => $row) {
            $stockItemRequest = new StockItemRequest([], $row);
            $stockItemRequest->setMethod('post');
            $stockItemRequest->setProductTypeRequired(true);
            if ($salesPriceRequired)
                $stockItemRequest->setSalesPriceRequired(true);
            /** @var Validator $validator */
            $validator = Validator::make($row, $stockItemRequest->rules(), $stockItemRequest->messages());
            $extraValidations = ['third_party_ref' => 'unique'];
            foreach (['serial', 'imei'] as $field) {
                if (isset($row[$field])) {
                    $extraValidations[$field] = 'uniqueOrEmpty';
                }
            }
            $validator->setArray($rows, $extraValidations);
            $verifier = app('validation.presence');
            /* $verifier->setConnection('stock'); */
            $validator->setPresenceVerifier($verifier);
            if ($validator->fails()) {
                $errors[] = ['rowIdx' => $i, 'errors' => $validator->errors()];
            }
        }

        return [$rows, $errors];
    }

    public function returnToStock()
    {
        $this->status = \App\Stock::STATUS_IN_STOCK;
        $this->locked_by = '';
        $this->sale_id = null;
        if ($this->original_sale_price > 0 && $this->original_sale_price != $this->sale_price) {
            $this->sale_price = $this->original_sale_price;
        }
        $this->save();
    }

    public function returnToBatch()
    {
        $this->status = Stock::STATUS_BATCH;
        $this->locked_by = '';
        $this->sale_id = null;
        if ($this->original_sale_price > 0 && $this->original_sale_price != $this->sale_price) {
            $this->sale_price = $this->original_sale_price;
        }
        $this->save();
    }

    public function addSerialToNotes($serial)
    {
        $notes = $this->notes;
        $notes = preg_replace('/Serial: \w+/', "Serial: $serial", $notes, -1, $count);
        if ($count === 0) {
            $notes .= ($notes ? "\n" : '') . "\nSerial: $serial";
        }
        $this->notes = $notes;
    }

    public function fillTrgItem(TrgItem $item)
    {
        preg_match('/\s(\d+)\s*gb/i', $item->product->product_name, $capacityMatch);

        $this->fill([
            'name' => $item->product->product_name,
            'capacity' => $capacityMatch ? $capacityMatch[1] : '',
            'serial' => $item->serial_number,
            'colour' => $item->colour_obj ? $item->colour_obj->pr_colour : $item->colour,
            'network' => $item->network,
            'trg_ref' => $item->item_ref,
            'third_party_ref' => $item->item_ref,
            'grade' => $item->stock_grade,
            'condition' => $item->stock_condition,
            'notes' => $item->eng_note ?: '',
            'lcd_status' => $item->lcd_status,
            'vendor_name' => $item->trade_in->vendor->site_name,
            'trg_product_number' => $item->product->product_id,
            'product_id' => $item->product->product_id,
        ]);

        $currencyConverter = app('currency_converter');

        // Selltronics Australia.
        if ($item->trade_in->vendor_id == 36) {
            $this->purchase_foreign_country = 'AU';
            $this->purchase_foreign_currency = 'AUD';
            $this->purchase_foreign_price = $item->actual_value;
            $this->purchase_price = $currencyConverter->convert($item->actual_value, 'AUD', 'GBP');
        } // Selltronics Ireland.
        elseif ($item->trade_in->vendor_id == 32) {
            $this->purchase_foreign_country = 'IE';
            $this->purchase_foreign_currency = 'EUR';
            $this->purchase_foreign_price = $item->actual_value;
            $this->purchase_price = $currencyConverter->convert($item->actual_value, 'EUR', 'GBP');
        } else {
            $this->purchase_price = $item->actual_value;
        }

        foreach ($item->general_log as $entry) {
            if (stripos($entry->task, 'moved to payment queue') !== false) {
                $this->purchase_date = $entry->date_time;
            }
        }

        if (!$this->purchase_date)
            $this->purchase_date = new Carbon();
    }

    public function blacklist_checks()
    {
        return $this->hasMany('App\Mobicode\Check', 'imei', 'imei');
    }

    public function unlock()
    {
        return $this->hasOne('App\Unlock');
    }

    public function imei_report()
    {
        return $this->hasOne('App\ImeiReport');
    }

    public function batch()
    {
        return $this->belongsTo('App\Batch');
    }

    public function lock_check()
    {
        return $this->hasOne('App\LockCheck');
    }

    public function network_checks()
    {
        return $this->hasMany('App\Mobicode\GsxCheck')->orderBy('id', 'desc');
    }

    public function sale_network_checks()
    {
        return $this->hasMany('App\Mobicode\GsxCheck')->whereNotNull('sale_id');
    }

    public function capacity_colour_network_check()
    {
        return $this->hasOne('App\Mobicode\GsxCheck')->where('service_id', GsxCheck::SERVICE_CAPACITY_COLOUR_CHECK);
    }

    public function icloud_status_check()
    {
        return $this->hasOne('App\Mobicode\GsxCheck')->where('service_id', GsxCheck::SERVICE_ICLOUD_STATUS_CHECK);
    }


    public function getUnlockMapping($n = null)
    {

        $network = $n ?: $this->network;

        if ($this->name && $this->make && $unlockMapping = UnlockMapping::where(function ($q) {
                $q->where('model', $this->name);
                $q->orWhere('model', str_replace('+', ' Plus', $this->name));
            })->where('make', $this->make)->where('network', $network)->first()) {
            return $unlockMapping;
        } elseif ($this->name && $unlockMapping = UnlockMapping::where(function ($q) {
                $q->where('model', $this->name);
                $q->orWhere('model', str_replace('+', ' Plus', $this->name));
            })->where('network', $network)->first()) {
            return $unlockMapping;
        } elseif ($this->make && $unlockMapping = UnlockMapping::where(function ($q) {
                $q->where('make', $this->make);
                $q->where('model', '');
            })->where('network', $network)->first()) {
            return $unlockMapping;
        } elseif ($this->name && $unlockMapping = UnlockMapping::where(function ($q) {
                $q->where('model', $this->name);
                $q->orWhere('model', str_replace('+', ' Plus', $this->name));
            })->where('network', 'All')->first()) {
            return $unlockMapping;
        } elseif ($this->name && $unlockMapping = UnlockMapping::where(function ($q) {
                $q->where('make', '');
                $q->where('model', '');
            })->where('network', $network)->first()) {
            return $unlockMapping;
        } elseif ($this->name && $unlockMapping = UnlockMapping::where(function ($q) {
                $q->where('make', $this->make);
                $q->where('model', '');
            })->where('network', 'All')->first()) {
            return $unlockMapping;
        } elseif ($this->name && $unlockMapping = UnlockMapping::where(function ($q) {
                $q->where('make', '');
                $q->where('model', '');
            })->where('network', 'All')->first()) {
            return $unlockMapping;
        }

        return null;
    }

    /**
     * Current non-void sale that this stock item is associated to.
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function sale()
    {
        return $this->belongsTo('App\Sale');
    }

    /**
     * All sales that this stock item is associated to - the live sale (if present) and past void sales (if present).
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function sale_history()
    {
        return $this->belongsToMany('App\Sale', 'new_sales_stock');
    }

    public function stockLogs()
    {
        return $this->hasMany('App\StockLog')->orderBy('id', 'DESC');
    }

    public function supplier()
    {
        return $this->belongsTo('App\Supplier');
    }

    public function phone_check()
    {
        return $this->hasOne('App\PhoneCheck');
    }

    public function saved_baskets()
    {
        return $this->belongsToMany('App\SavedBasket', "saved_baskets_stock")->withPivot('created_at');
    }

    public function parts()
    {
        return $this->belongsToMany('App\Part', 'new_stock_parts')->withPivot('cost', 'created_at');
    }

    public function stock_parts()
    {
        return $this->hasMany('App\StockPart');
    }

    public function product()
    {
        return $this->belongsTo('App\Product');
    }

    public function stock_takes()
    {
        return $this->hasMany('App\StockTake');
    }

    public function repairs()
    {
        return $this->hasMany('App\Repair', 'item_id', 'id');
    }

    public function scopeShownToUser(Builder $query, User $user)
    {

        if ($user->type !== 'user') {
            return;
        }

        $query->where(function ($subWhere) use ($user) {
            $subWhere->whereNotIn('status', [self::STATUS_INBOUND, self::STATUS_IN_STOCK]);
            //$subWhere->orWhereIn('shown_to', [self::SHOWN_TO_ALL, self::SHOWN_TO_EBAY, self::SHOWN_TO_EBAY_AND_SHOP]);
            $subWhere->orWhere(function ($q) {
                $q->where(function ($w) {
                    $w->whereIn('shown_to', [self::SHOWN_TO_EBAY, self::SHOWN_TO_EBAY_AND_SHOP]);
                    $w->whereRaw('sale_price >= purchase_price*1.2');
                });
                $q->orWhere('shown_to', self::SHOWN_TO_ALL);
            });
        });
    }

    /**
     * @param Builder $query
     * @param string $ref
     * @param string|array $except One or more of 'imei', 'serial', 'third_party_ref'
     * @throws Exception
     */
    public function scopeMultiRef(Builder $query, $ref, $except = [])
    {
        if (!is_array($except))
            $except = [$except];

        $ref = trim($ref);
        if (!$ref) {
            throw new Exception("Multi ref error \$ref cannot be empty.");
        }

        $query->where(function ($where) use ($ref, $except) {
            if (!in_array('imei', $except)) {
                $where->orWhere('imei', $ref);
            }
            if (!in_array('serial', $except)) {
                $where->orWhere('serial', $ref);
            }
            if (!in_array('third_party_ref', $except)) {
                $where->orWhere('third_party_ref', $ref);
            }
        });
    }

    public function scopeForPreOrder(Builder $query, PreOrder $preOrder, $lockKey = null)
    {
        $query
            ->status($preOrder->user->allowed_statuses_buying)
            ->where('name', $preOrder->name);

        if ($preOrder->capacity !== 'Any') {
            $query->where('capacity', $preOrder->capacity);
        }
        if ($preOrder->colour !== 'Any') {
            $query->where('colour', $preOrder->colour);
        }
        if ($preOrder->condition !== 'Any') {
            $query->where('condition', $preOrder->condition);
        }

        $query
            ->where('grade', $preOrder->grade)
            ->whereIn('network', $preOrder->networks_enabled)
            ->where('sale_price', '>', 0)
            ->where('sale_price', '<=', $preOrder->price)
            ->where('locked_by', $lockKey ?: '');
    }

    /**
     * @param Builder $query
     * @param string|array $statuses
     */
    public function scopeSkuOverview(Builder $query, $statuses = null)
    {
        if (!$statuses) {
            return;
        }

        if ($statuses && !is_array($statuses)) {
            $statuses = [$statuses];
        }


        $suffixes = [
            self::STATUS_INBOUND => 'inbound',
            self::STATUS_IN_STOCK => 'in_stock',
            self::STATUS_RE_TEST => 're_test',
            self::STATUS_BATCH => 'batch',
            self::STATUS_REPAIR => 'in_repair',
            // self::STATUS_RETURNED_TO_SUPPLIER => "returned_to_supplier",
            self::STATUS_3RD_PARTY => "3rd_party",
            self::STATUS_READY_FOR_SALE => "ready_for_sale",
            self::STATUS_RETAIL_STOCK => "retail_stock",
            self::STATUS_LISTED_ON_AUCTION => "listed_on_auction",
            self::STATUS_RESERVED_FOR_ORDER => "reserved_for_order",
            //  self::STATUS_EBAY_LISTED=>'listed_on_eBay',
            self::STATUS_ALLOCATED=>'allocated',
        ];


        $query->select(DB::raw('sql_calc_found_rows *'));
        foreach ($statuses as $status) {
            $suffix = $suffixes[$status];
            $query->addSelect(DB::raw("sum(if(status = " . DB::connection()->getPdo()->quote($status) . ", 1, 0)) count_$suffix"));
            //   $query->orderBy("count_$suffix", "desc");

        }

        $query->groupBy('product_id')->groupBy('grade')->groupBy('status')->groupBy('vat_type');
    }

    public function scopeNetwork(Builder $query, $value)
    {
        if ($value) {
            $query->where('network', $value);
        }
    }

    public function scopeQuery(Builder $query, $value)
    {
        if (!$value) {
            return;
        }

        // We split on whitespace followed by something other than a digit. We want to split for instance
        // "Samsung Galaxy S8" into ["Samsung", 'Galaxy", "S8"] but "Apple iPhone 6" into ["Apple", "iPhone 6"].
        // Digits on their own as query terms can cause unwanted matches, for instance a query for "iPhone 6" could
        // match "iPhone 5 16GB" if we just split the query on whitespace.
        $tokens = preg_split('/\s+(?!\d)/', $value);


        // $tokens = [$value];
        foreach ($tokens as $token) {
            $query->where(function ($subWhere) use ($token) {
                foreach (['id', 'name', 'network', 'colour', 'serial', 'imei', 'third_party_ref', 'sku'] as $field) {
                    // If they explicitly started the query with "TRG", allow searching the id. Other fields will be
                    // searchable by this token too, but they'd have to include the "TRG" part.
                    if ($field === 'id' && strtolower(substr($token, 0, 3)) === 'rct') {
                        $subWhere->orWhere($field, 'like', "%" . substr($token, 3) . "%");
                    }
                    else {

                        $subWhere->orWhere($field,'REGEXP','[[:<:]]'.$token.'[[:>:]]');
                    }
                }
            });
            if (strlen($token) === 15) {
                $query->orWhere('sku', $token);
            }
        }
    }

    public function scopeGrade(Builder $query, $value)
    {
        if ($value)
            $query->where('grade', $value);
    }

    public function scopeCondition(Builder $query, $value)
    {
        if ($value)
            $query->where('condition', $value);
    }

    public function scopeColour(Builder $query, $value)
    {
        if ($value)
            $query->where('colour', $value);
    }

    public function scopeCapacity(Builder $query, $value)
    {
        if ($value)
            $query->where('capacity', $value);
    }

    public function scopeTouchIdWorking(Builder $query, $value)
    {
        if ($value)
            $query->where('touch_id_working', $value);
    }

    public function scopeCrackedBack(Builder $query, $value)
    {
        if ($value)
            $query->where('cracked_back', $value);
    }

    public function scopeProductType(Builder $query, $value)
    {
        if ($value)
            $query->where('product_type', $value);
    }

    public function scopeTestStatus(Builder $query, $value)
    {
        if ($value)
            $query->where('test_status', $value);
    }


    public function scopeStatus(Builder $query, $value)
    {
        if ($value && is_array($value)) {
            $query->whereIn('status', $value);
        } elseif ($value) {
            $query->where('status', $value);
        }
    }
    public function scopeMpnMapping(Builder $query, $value)
    {
        if ($value==="1"){
            $query->where('mpn_map', "1");
        }elseif($value ==="0"){
            $query->where('mpn_map', "0");
        }

    }

    public function getMarginFormattedAttribute()
    {
        return $this->margin . "%";
    }

    public function getInWarrantyAttribute()
    {
        if ($this->first_unbrick_at && Carbon::parse($this->first_unbrick_at)->diffInDays(Carbon::now()) < 365 && $this->show_warranty) {
            return true;
        } else {
            return false;
        }
    }

    public function getInWarrantyEligibleAttribute()
    {
        return ($this->first_unbrick_at && Carbon::parse($this->first_unbrick_at)->diffInDays(Carbon::now()) < 365);
    }

    public function getDeviceLoggedAsNotLockedAttribute()
    {
        if ($this->stockLogs()->where('content', 'like', 'This device is iCloud free as of %')->first()) {
            return true;
        }

        if ($this->stockLogs()->where('content', 'like', 'This device has an iCloud account')->first()) {
            return false;
        }

        return false;
    }

    public function getReturnRejectionReasonAttribute()
    {
        if ($this->eligible_for_return) {
            return null;
        }

        return $this->_return_rejection_reason;
    }

    public function getSoldAttribute()
    {
        return !!$this->sale_id;
    }

    public function getPurchaseDateAttribute($value)
    {
        if (!$value || $value[0] === '0') {
            return null;
        } else {
            return Carbon::createFromFormat('Y-m-d H:i:s', $value);
        }
    }

    public function getOurRefAttribute()
    {
        return 'RCT' . str_pad($this->id, 3, '0', STR_PAD_LEFT);
    }

    public function getCapacityAttribute($value)
    {
        return $value ?: null;
    }

    public function getCapacityFormattedAttribute()
    {
        $contains = str_contains($this->name, 'TB');
        if($contains){
            return $this->capacity . 'TB';
        }
        return $this->capacity ? $this->capacity . 'GB' : '';
    }

    public function getLongNameAttribute()
    {

        if($this->name_compare){
            $name = $this->make.' '.str_replace( array('@rt'), 'GB', $this->name).' ('.$this->condition.')';


        }else{
            $name = $this->name;
            if ($this->capacity) {
                $name .= ' - ' . $this->capacity_formatted;
            }
            $name.=' ('.$this->condition.')';
        }


        if ($this->network) {
            $name .= ' - ' . $this->network;
        }
        if ($this->grade) {
            $name .= ' - ' . $this->grade;
        }
        return $name;
    }

    public function getLongNameWithoutNetworkAttribute()
    {


        if($this->name_compare){
            $name = $this->make.' '.str_replace( array('@rt'), 'GB', $this->name).' ('. $this->condition .')';

        }else{
            $name =$this->make.' '.$this->name;
            if ($this->capacity) {
                $name .= ' - ' . $this->capacity_formatted;
            }
            $name .=' ('.$this->condition.')';
        }

        if ($this->grade) {
            $name .= ' - ' . $this->grade;
        }
        if ($this->imei) {
            $name .= ' - ' . $this->imei;
        }elseif($this->serial){
            $name .= ' - ' . $this->serial;
        }


        return $name;
    }

    public function getLongNameWithoutGradeAttribute()
    {
        $name = $this->name;
        if ($this->capacity) {
            $name .= ' - ' . $this->capacity_formatted;
        }
        if ($this->imei) {
            $name .= ' - ' . $this->imei;
        }else{
            $name .= ' - ' . $this->serial;
        }
        return $name;
    }

    public function getShouldBeSoldAsAttribute()
    {
        if ($this->status != self::STATUS_IN_STOCK)
            return;

        $res = [];
        if ($this->touch_id_working == self::TOUCH_ID_WORKING_NO ||
            ($this->touch_id_working == self::TOUCH_ID_WORKING_YES && $this->lcd_status == self::LCD_GOOD_AND_GLASS_SLIGHT_BLEMISH)) {
            $res[] = self::SOLD_AS_EBAY;
        }

        if ($this->touch_id_working == self::TOUCH_ID_WORKING_YES &&
            in_array($this->condition, [self::CONDITION_B, self::CONDITION_C]) && count($this->faults)) {
            $res[] = self::SOLD_AS_WHOLESALE_MINOR_FAULTS;
        }

        if ($this->grade == self::GRADE_FULLY_WORKING && $this->touch_id_working == self::TOUCH_ID_WORKING_YES) {
            $res[] = self::SOLD_AS_WHOLESALE_FULLY_WORKING;
        }

        if ($this->touch_id_working == self::TOUCH_ID_WORKING_YES && $this->condition == self::CONDITION_D) {
            $res[] = self::SOLD_AS_REFURBISH_AND_SELL;
        }

        if ($this->touch_id_working == self::TOUCH_ID_WORKING_YES && $this->condition == self::CONDITION_A) {
            $res[] = self::SOLD_AS_REPAIR_AND_SELL;
        }

        $res = implode(" or ", $res);
        if (!$res) {
            $res = '-';
        }
        return $res;
    }

    public function getDaysOldAttribute()
    {
        return $this->created_at->diffInDays(Carbon::now());
    }

    public function getPhoneCheckPassedAttribute()
    {
        if ($this->phone_check && $this->phone_check->status == PhoneCheck::STATUS_DONE) {
            return $this->make . " " . $this->name . " " . $this->capacity_formatted . " - " . $this->colour . " - " . $this->network;
        }

        return false;
    }

    public function getPhoneCheckColourEditAttribute()
    {
        $allowed_users = ['chris@recommercetech.co.uk'];
        try {
            if ($this->phone_check_passed) {
                $report = json_decode($this->phone_check->response);
                if (!$report->Color) {
                    return true;
                } elseif ($report->Color && Auth::user() && in_array(Auth::user()->email, $allowed_users)) {
                    return true;
                }
            }
        } catch (Exception $e) {
            return false;
        }
    }

    public function getGradeFullyWorkingAvailableAttribute()
    {
        try {
            if ($this->phone_check && $this->phone_check->status == PhoneCheck::STATUS_DONE) {
                $report = json_decode($this->phone_check->response);
                if ($report->BatteryHealthPercentage && $report->BatteryHealthPercentage < 80) {
                    return false;
                }
            }
        } catch (Exception $e) {
            return true;
        }

        return true;
    }

    public function getFailedMdmAttribute()
    {
        try {
            if ($this->phone_check && $this->phone_check->status == PhoneCheck::STATUS_DONE) {
                $report = json_decode($this->phone_check->response);
                if (strpos($report->Failed, 'MDM') != false) {
                    return true;
                }
            }
        } catch (Exception $e) {
            return false;
        }

        return false;
    }

    public function getBatteryLifeAttribute()
    {
        try {
            if ($this->phone_check && $this->phone_check->status == PhoneCheck::STATUS_DONE) {
                $report = json_decode($this->phone_check->response);
                return $report->BatteryHealthPercentage;
            }
        } catch (Exception $e) {
            return false;
        }
    }

    public function getIcloudStatusAttribute()
    {
        try {
            $networkCheck = $this->network_checks()->orderBy('id', 'desc')->where('status', GsxCheck::STATUS_DONE)->first();
            if ($networkCheck) {
                $report = $networkCheck->report;

                if (preg_match('/GSMA\/Blacklist: <font color="#\d{6}">(?<status>.*?)(\.|\s*<\/font>)/', $report, $status) && isset($status['status'])) {
                    $status = $status['status'];
                    return $status;
                }
            }
            $icloudCheck = $this->icloud_status_check()->where('status', GsxCheck::STATUS_DONE)->first();
            if ($icloudCheck) {
                $report = $icloudCheck->report;
                if (preg_match('/Find My iPhone: <span style="color:\w{2,5};">(?<status>.*?)(\.|\s*<\/span>)/', $report, $status) && isset($status['status'])) {
                    $status = $status['status'];
                    return $status;
                }
            }
        } catch (Exception $e) {
            return '';
        }
    }

    public static function getAvailableLcdStatuses($includeEmpty = true)
    {
        $statuses = [];
        if ($includeEmpty) {
            $statuses[] = self::LCD_UNSPECIFIED;
        }
        return array_merge($statuses, [
            self::LCD_GOOD_GLASS_GOOD,
            self::LCD_GOOD_GLASS_BAD,
            self::LCD_BAD_GLASS_GOOD,
            self::LCD_BAD_GLASS_BAD,
            self::LCD_GOOD_AND_GLASS_SLIGHT_BLEMISH,
        ]);
    }

    public static function getAvailableShownTo()
    {
        return [self::SHOWN_TO_NONE, self::SHOWN_TO_ALL, self::SHOWN_TO_EBAY, self::SHOWN_TO_EBAY_AUCTION, self::SHOWN_TO_AMAZON_FBA];
    }

    public static function getAvailableShownToWithKeys()
    {
        return array_combine(self::getAvailableShownTo(), self::getAvailableShownTo());
    }

    public static function getAvailableLcdStatusesWithKeys()
    {
        $statuses = array_combine(self::getAvailableLcdStatuses(), self::getAvailableLcdStatuses());
        $statuses[''] = 'Unspecified';
        return $statuses;
    }

    public static function getAvailableTouchIdWorking()
    {
        return [self::TOUCH_ID_WORKING_YES, self::TOUCH_ID_WORKING_NO, self::TOUCH_ID_WORKING_NA,self::TOUCH_ID_UNSURE];
    }

    public static function getAvailableTouchIdWorkingWithKeys()
    {
        return array_combine(self::getAvailableTouchIdWorking(), self::getAvailableTouchIdWorking());
    }

    public static function getAvailableCrackedBack()
    {
        return [self::CRACKED_BACK_YES, self::CRACKED_BACK_No];
    }


    public static function getAvailableTestStatusWithKeys()
    {
        return array_combine(self::getAvailableTestStatus(), self::getAvailableTestStatus());
    }

    public static function getAvailableTestStatus()
    {
        return [self::TEST_STATUS_COMPLETE, self::TEST_STATUS_PENDING,self::TEST_STATUS_UNTESTED];
    }

    public static function getAvailableCrackedBackWithKeys()
    {
        return array_combine(self::getAvailableCrackedBack(), self::getAvailableCrackedBack());
    }

    public static function getAvailableFaults()
    {
        return [self::FAULT_CHARGING_PORT, self::FAULT_FRONT_CAMERA, self::FAULT_REAR_CAMERA, self::FAULT_VOLUME_BUTTONS,
            self::FAULT_POWER_BUTTON, self::FAULT_HEADPHONE_JACK, self::FAULT_LOUD_SPEAKER, self::FAULT_PROXIMITY_SENSOR,
            self::FAULT_EAR_SPEAKER, self::FAULT_MICROPHONE, self::FAULT_FLASH, self::FAULT_TORCH, self::FAULT_VIBRATION,
            self::FAULT_BROKEN_SCREEN, self::FAULT_BATTERY, self::FAULT_MUTE_SWITCH];
    }

    public static function getAvailableFaultsWithKeys()
    {
        return array_combine(self::getAvailableFaults(), self::getAvailableFaults());
    }

    public static function getAvailableNetworks()
    {
        if (!self::$availableNetworks) {
            self::$availableNetworks = Network::customOrder()->lists('pr_network');
        }

        return self::$availableNetworks;
    }

    public static function getAllAvailableNetworks()
    {
        $GetNetworks = Network::customOrder()->whereIn("country", ["US", "UK", "Others"])->get();

        $NetworkFilter['USA'] = [];
        $NetworkFilter['United Kingdom'] = [];
        $NetworkFilter['Others'] = [];

        foreach ($GetNetworks as $network) {
            if ($network->country == "US") {
                array_push($NetworkFilter['USA'], $network->pr_network);
            } elseif ($network->country == "UK") {
                array_push($NetworkFilter['United Kingdom'], $network->pr_network);
            } else if ($network->country == "Others") {
                array_push($NetworkFilter['Others'], $network->pr_network);
            }
        }

        return $NetworkFilter;
    }

    public static function getAvailableNetworksWithKeys()
    {
        return array_combine(self::getAvailableNetworks(), self::getAvailableNetworks());
    }

    public static function getAvailableNetworksUs()
    {
        return ['Unknown', 'Unlocked', 'Foreign Network', 'AT&T', 'US GSM', 'T-Mobile USA', 'Sprint USA'];
    }

    public static function getAvailableNetworksUsWithKeys()
    {
        return array_combine(self::getAvailableNetworksUs(), self::getAvailableNetworksUs());
    }

    public static function getAvailableStatuses()
    {
        if (!self::$availableStatuses) {
            self::$availableStatuses = enum_values('new_stock', 'status', (new self)->getConnectionName());
        }

        return self::$availableStatuses;
    }

    public static function getAvailableStatusesWithKeys()
    {
        return array_combine(self::getAvailableStatuses(), self::getAvailableStatuses());
    }

    /**
     * @return array - iphones grades
     */
    public static function getAvailableGrades($option = null)
    {
        if (!$option) {
            return [
                self::GRADE_FULLY_WORKING_NO_TOUCH_ID,
                self::GRADE_FULLY_WORKING_NO_FACE_ID,
                self::GRADE_FULLY_WORKING,
                self::GRADE_MINOR_FAULT,
                self::GRADE_MAJOR_FAULT,
                self::GRADE_BROKEN,
                self::GRADE_LOCKED,
                self::GRADE_LOCKED_CLEAN,
                self::GRADE_LOCKED_LOST,
                self::GRADE_SHOP_GRADE,
                self::GRADE_NEW,
                self::GRADE_BLACKLISTED
            ];
        } elseif (strtolower($option) == 'all') {
            return enum_values('new_stock', 'grade', (new self)->getConnectionName());
        } elseif (strtolower($option) == 'samsung') {
            return [
                self::GRADE_FULLY_WORKING,
                self::GRADE_WARRANTY_REPAIR,
                self::GRADE_FULLY_WORKING_CRACKED_GLASS,
                self::GRADE_FULLY_WORKING_CRACKED_BACK,
                self::GRADE_FULLY_WORKING_NO_FINGERPRINT_SENSOR,
                self::GRADE_MINOR_FAULT,
                self::GRADE_AWAITING_BATTERY_PART,
                self::GRADE_BAD_LCD_SLIGHT_BLEMISH,
                self::GRADE_BAD_LCD_FULLY_BROKEN,
                self::GRADE_MAJOR_FAULT,
                self::GRADE_BROKEN,
                self::GRADE_SHOP_GRADE,
                self::GRADE_NEW,
                self::GRADE_BLACKLISTED
            ];
        }
    }

    public static function getAvailableGradesWithKeys($option = null)
    {
        return array_combine(self::getAvailableGrades($option), self::getAvailableGrades($option));
    }

    public static function getAvailableConditions()
    {
        return [self::CONDITION_A, self::CONDITION_B, self::CONDITION_C, self::CONDITION_D,self::CONDITION_E];
    }

    public static function getAvailableConditionsWithKeys()
    {
        return array_combine(self::getAvailableConditions(), self::getAvailableConditions());
    }

    public static function getAvailableCapacityWithKeys()
    {
        $capacity = [
            '4' => '4 GB',
            '8' => '8 GB',
            '16' => '16 GB',
            '32' => '32 GB',
            '64' => '64 GB',
            '128' => '128 GB',
            '256' => '256 GB'
        ];
        return $capacity;
    }

    public function getAvailableColours()
    {
        if (strpos(strtolower($this->name), "ipad 1") !== false) {
            $colours = ['Black'];
        } elseif (
            (strpos(strtolower($this->name), "ipad 2") !== false) ||
            (strpos(strtolower($this->name), "ipad 3") !== false) ||
            (strpos(strtolower($this->name), "ipad 4") !== false) ||
            (strpos(strtolower($this->name), "ipad mini 1") !== false) ||
            (strpos(strtolower($this->name), "ipad mini 2") !== false)
        ) {
            $colours = ["Black", "White"];
        } elseif (strpos(strtolower($this->name), "iphone 5s") !== false) {
            $colours = ['Space Grey', 'Silver', 'Gold'];
        } elseif (strpos(strtolower($this->name), "iphone 5c") !== false) {
            $colours = ['Yellow', 'Blue', 'White', 'Green', 'Pink'];
        } elseif (
            (strpos(strtolower($this->name), "iphone 4") !== false) ||
            (strpos(strtolower($this->name), "iphone 5") !== false)
        ) {
            $colours = ["Black", "White"];
        } elseif (strpos(strtolower($this->name), "iphone 6s plus") !== false) {
            $colours = ['Gold', 'Rose Gold', 'Silver', 'Space Grey'];
        } elseif (strpos(strtolower($this->name), "iphone 6 plus") !== false) {
            $colours = ['Gold', 'Silver', 'Space Grey'];
        } elseif (
            (strpos(strtolower($this->name), "iphone se") !== false) ||
            (strpos(strtolower($this->name), "iphone 6s") !== false)
        ) {
            $colours = ["Space Grey", "Silver", "Gold", "Rose Gold"];
        } elseif (strpos(strtolower($this->name), "iphone 6") !== false) {
            $colours = ['Space Grey', 'Silver', 'Gold'];
        } else {
            $colours = Colour::orderBy('pr_colour')->lists('pr_colour');;
        }


        return $colours;
    }

    public function getAvailableColoursWithKeys()
    {
        return array_combine(self::getAvailableColours(), self::getAvailableColours());
    }

    public static function getAvailablePurchaseCountries()
    {
        return [self::PURCHASE_COUNTRY_UK, self::PURCHASE_COUNTRY_US];
    }

    public static function getAvailablePurchaseCountriesWithKeys()
    {
        return array_combine(self::getAvailablePurchaseCountries(), self::getAvailablePurchaseCountries());
    }

    public static function getAvailableLostReasons()
    {
        return [self::LOST_REASON_LOST_BY_COURIER, self::LOST_REASON_LOST_AT_TRG, self::LOST_REASON_REPORTED_LOST_BY_CUSTOMER];
    }

    public static function getAvailableLostReasonsWithKeys()
    {
        return array_combine(self::getAvailableLostReasons(), self::getAvailableLostReasons());
    }

    public static function getAvailableProductTypes()
    {
        return [self::PRODUCT_TYPE_MOBILE_PHONE, self::PRODUCT_TYPE_TABLET, self::PRODUCT_TYPE_DESKTOP, self::PRODUCT_TYPE_COMPUTER, self::PRODUCT_TYPE_ACCESSORIES];
    }

    public static function getAvailableProductTypesWithKeys()
    {
        return array_combine(self::getAvailableProductTypes(), self::getAvailableProductTypes());
    }

    public function getPurchaseCountryAttribute()
    {
        if ($this->attributes['purchase_country'] == '')
            return self::PURCHASE_COUNTRY_UK;

        return $this->attributes['purchase_country'];
    }

    public function getPurchaseCountryFlagAttribute()
    {
        $country = $this->purchase_country == self::PURCHASE_COUNTRY_UK ? 'GB' : $this->purchase_country;
        return asset('img/stripe-flag-set/' . $country . '.png');
    }

    public function setNotesAttribute($value)
    {
        $value = preg_replace('/\s*beyond economical repair\s*/i', ' ', $value);
        $value = preg_replace('/(?<!\w)BER(?!\w)/', ' ', $value);
        $value = trim($value);
        $this->attributes['notes'] = $value;
    }

    public function setNameAttribute($value)
    {
        // Remove capacity.
        $regex = " (\d+) *gb(?= |$)";
        if (preg_match("/$regex/i", $value, $capacityMatch)) {
            $value = preg_replace("/[ -]*$regex/i", '', $value);
        }

        // Remove some unnecessary words.
        $value = preg_replace('/\s*(apple|samsung)\s*/i', ' ', $value);
        $value = trim($value);

        $this->attributes['name'] = $value;
    }

    public function getFreeUnlockEligibleAttribute()
    {
        return
            preg_match('/^\d{15,16}$/', $this->imei) &&
            $this->sale_price > 30 &&
            stripos($this->name, 'iphone') !== false &&
            in_array($this->network, self::$unlockableFree) &&
            in_array($this->grade, [self::GRADE_MINOR_FAULT, self::GRADE_FULLY_WORKING]);
    }

    public function getVodafoneUnableToUnlockAttribute()
    {
        if (
            ($this->free_unlock_eligible && $this->network == "Vodafone" && $this->vendor_name) ||
            (strpos($this->third_party_ref, 'T000000') === 0 && $this->free_unlock_eligible && $this->network == "Vodafone")
        ) {
            return true;
        }
        return false;
    }

    public function setThirdPartyRefAttribute($value)
    {
        if (!trim($value)) {
            $value = null;
        }

        $this->attributes['third_party_ref'] = $value;
    }

    public function getOrderhubSkuAttribute()
    {
        $models = [
            "iphone 4" => "IP4",
            "iphone 4s" => "IP4S",
            "iphone 5" => "IP5",
            "iphone 5c" => "IP5C",
            "iphone 5s" => "IP5S",
            "iphone 6" => "IP6",
            "iphone 6 plus" => "IP6P",
            "iphone 6s" => "IP6S",
            "iphone 6s plus" => "IP6SP",
            "iphone se" => "IPSE",
            "iphone 7" => "IP7",
            "ipad 1" => "IPAD",
            "ipad 2" => "IPAD2",
            "ipad 3" => "IPAD3",
            "ipad 4" => "IPAD4",
            "ipad mini" => "IPADMINI",
            "ipad mini 1" => "IPADMINI",
            "ipad mini 2" => "IPADMINI2",
            "ipad mini 3" => "IPADMINI3",
            "ipad air" => "IPADAIR",
            "ipad air 2" => "IPADAIR2"
        ];

        $capacities = [
            "8" => "8",
            "16" => "16",
            "32" => "32",
            "64" => "64",
            "128" => "128",
            "256" => "256"
        ];

        $ipadsAdditional = [
            "wifi only" => "WIFI",
            "wifi + cellular" => "cellular",
            "wi-fi + cellular" => "cellular",
            "wifi and cellular" => "cellular",
            "wi-fi and cellular" => "cellular",
            "wifi & 3g" => "3G",
            "wifi + 3g" => "3G",
            "wifi and 3g" => "3G",
            "wi-fi & 3g" => "3G",
            "wi-fi + 3g" => "3G",
            "wi-fi and 3g" => "3G",
            "wifi + 4g" => "4G",
            "wifi & 4g" => "4G",
            "wifi and 4g" => "4G",
            "wi-fi + 4g" => "4G",
            "wi-fi & 4g" => "4G",
            "wi-fi and 4g" => "4G",
            "with wifi" => "WIFI",
            "wifi" => "WIFI",
            "wi-fi" => "WIFI",
            "3g" => "3G",
            "4g" => "4G",
        ];

        $colours = [
            "space grey" => "SG",
            "rose gold" => "RG",
            "silver" => "SV",
            "gold" => "GL",
            "black" => "BK",
            "white" => "WH",
            "yellow" => "YE",
            "pink" => "PK",
            "blue" => "BL",
            "green" => "GR",
            "coral" => "PK"
        ];

        $networks = [
            "unlocked" => "UL",
            "ee" => "EE",
            "o2" => "O2",
            "vodafone" => "VODA",
            "three" => "3",
            "wifi" => "WIFI",
            "orange" => "OR"
        ];

        $grades = [
            "a" => "A",
            "b" => "B",
            "c" => "C",
            "d" => "D"
        ];

        $sku = "";
        if ($this->name && (strpos(strtolower($this->name), "iphone") !== false || strpos(strtolower($this->name), "ipad") !== false)) {
            $name = $this->name;
            if (strpos(strtolower($name), "ipad") !== false) {
                foreach ($ipadsAdditional as $key => $value) {
                    if (strpos(strtolower($name), $key) !== false) {
                        $name = str_replace(" " . $key, "", strtolower($name));
                        break;
                    }
                }
            }
            if (isset($models[strtolower($name)])) {
                $sku .= $models[strtolower($name)];
            }
            if ($this->grade == self::GRADE_FULLY_WORKING_NO_TOUCH_ID) {
                $sku .= "NT";
            }
            if ($this->grade == self::GRADE_FULLY_WORKING_NO_FACE_ID) {
                $sku .= "FT";
            }
            $sku .= "-";
        }
        if ($this->capacity && isset($capacities[$this->capacity])) {
            $sku .= $capacities[$this->capacity] . "-";
        }
        if ($this->colour && isset($colours[strtolower($this->colour)])) {
            $sku .= $colours[strtolower($this->colour)] . "-";
        }

        if (strpos(strtolower($this->name), "ipad") !== false) {
            if (strpos(strtolower($this->name), 'cellular') !== false) {
                if (strpos(strtolower($this->name), 'ipad mini 1') !== false) {
                    $sku .= "3G-";
                } elseif (strpos(strtolower($this->name), 'ipad mini 2') !== false) {
                    $sku .= "3G-";
                } elseif (strpos(strtolower($this->name), 'ipad mini 3') !== false) {
                    $sku .= "4G-";
                } elseif (strpos(strtolower($this->name), 'ipad mini') !== false) {
                    $sku .= "3G-";
                } elseif (strpos(strtolower($this->name), 'ipad 1') !== false) {
                    $sku .= "3G-";
                } elseif (strpos(strtolower($this->name), 'ipad 2') !== false) {
                    $sku .= "3G-";
                } elseif (strpos(strtolower($this->name), 'ipad 3') !== false) {
                    $sku .= "4G-";
                } elseif (strpos(strtolower($this->name), 'ipad 4') !== false) {
                    $sku .= "4G-";
                } elseif (strpos(strtolower($this->name), 'ipad air') !== false) {
                    $sku .= "4G-";
                }
            } else {
                foreach ($ipadsAdditional as $key => $val) {
                    if (strpos(strtolower($this->name), strtolower($key)) !== false) {
                        $sku .= $val . "-";
                        break;
                    }
                }
            }
        } else {
            if ($this->network && isset($networks[strtolower($this->network)])) {
                $sku .= $networks[strtolower($this->network)] . "-";
            }
        }
        if ($this->condition && isset($grades[strtolower($this->condition)])) {
            $sku .= $grades[strtolower($this->condition)];
        }

        return $sku;
    }

    public function getSkuAttribute($value = null)
    {
        if ($value) {
            return $value;
        }

        $sku = '';

        foreach (self::$skuAttributes as $attrName) {
            $sku .= Sku::getShort($attrName, $this->$attrName);
        }

        return $sku;
    }

    public function getPurchasePriceFormattedAttribute()
    {
        return $this->purchase_price ? money_format(config('app.money_format'), $this->purchase_price) : '';
    }

    public function getPurchasePriceNoCostsAttribute()
    {
        $partsPrice = 0;
        $unlockPrice = 0;
        if ($this->parts) {
            foreach ($this->parts as $part) {
                $partsPrice += $part->cost;
            }
        }
        if ($this->unlock) {
            $unlockPrice = $this->unlock->cost_added;
        }
        $price = $this->purchase_price - $unlockPrice - $partsPrice;
        return $price ? money_format(config('app.money_format'), $price) : "";
    }

    public function getPurchaseForeignPriceFormattedAttribute()
    {
        $currencyConverter = app('currency_converter');
        return $this->purchase_foreign_price ? $currencyConverter->getSign($this->purchase_foreign_currency) . number_format($this->purchase_foreign_price, 2) : '';
    }

    public function getSalePriceFormattedAttribute()
    {
        return $this->sale_price ? money_format(config('app.money_format'), $this->sale_price) : '';
    }

    public function getNetworkFormattedAttribute()
    {
        if ($this->network == 'Unlock Requested') {
            return "Unlocked";
        }

        return $this->network;
    }

    public function getUnlockAvailableErrorsAttribute()
    {
        $errors = [];
        if (!$this->imei) {
            $errors[] = "IMEI missing";
        }
        if ($this->imei && strlen($this->imei) != 15) {
            $errors[] = "IMEI must be 15 digits long";
        }
        if ($this->unlock) {
            $errors[] = "Unlock already exists";
        }
        if (!in_array($this->network, self::getAdminUnlockableNetworks())) {
            $errors[] = "This network can't be unlocked";
        }

        return count($errors) ? implode(", ", $errors) : false;
    }

    public function getGrossProfitAttribute()
    {
        return $this->sale_price - $this->purchase_price - $this->unlock_cost - $this->part_cost;
    }

    public function getGrossProfitFormattedAttribute()
    {
        return money_format(config('app.money_format'), $this->gross_profit);
    }

    public function getGrossProfitPercentageAttribute()
    {
        if ($this->sale_price > 0) {
            return number_format($this->gross_profit / $this->sale_price * 100, 2);
        } else {
            return 0;
        }
    }

    public function getGrossProfitPercentageFormattedAttribute()
    {
        return $this->gross_profit_percentage . "%";
    }

    public function getTotalGrossProfitAttribute()
    {
        return $this->sale_price - $this->purchase_price - $this->unlock_cost - $this->part_cost - $this->vat;
    }

    public function getTotalGrossProfitFormattedAttribute()
    {
        return money_format(config('app.money_format'), $this->total_gross_profit);
    }

    public function getTotalGrossProfitPercentageAttribute()
    {
        if ($this->sale_price > 0) {
            return number_format($this->total_gross_profit / $this->sale_price * 100, 2);
        } else {
            return 0;
        }
    }

    public function getTotalGrossProfitPercentageFormattedAttribute()
    {
        return $this->total_gross_profit_percentage . "%";
    }

    public function getVatAttribute()
    {
        if ($this->purchase_country == self::PURCHASE_COUNTRY_US) {
            return $this->sale_price * 0.2;
        } else {
            return ($this->sale_price - $this->purchase_price) * 0.1667;
        }
    }

    public function getVatFormattedAttribute()
    {
        return money_format(config('app.money_format'), $this->vat);
    }

    public function getNetProfitAttribute()
    {
        return $this->gross_profit - $this->vat;
    }

    public function getNetProfitFormattedAttribute()
    {
        return money_format(config('app.money_format'), $this->net_profit);
    }

    public function getUnlockCostFormattedAttribute()
    {
        return money_format(config('app.money_format'), $this->unlock_cost);
    }

    public function getPartCostFormattedAttribute()
    {
        return money_format(config('app.money_format'), $this->part_cost);
    }

    public function getTotalCostsAttribute()
    {
        return $this->purchase_price + $this->unlock_cost ;
    }

    public function getTotalCostWithRepairAttribute(){

        $repairCost=0;
        if(count($this->repair_item)>0){

            foreach ($this->repair_item as $repair){
                if($repair->type===RepairsItems::TYPE_EXTERNAL){
                    $repairCost=$repair->actual_repair_cost<1 ? $repair->estimate_repair_cost:$repair->actual_repair_cost;
                }
            }

        }


        $totalPurchaseCost=$repairCost + $this->purchase_price + $this->unlock_cost + $this->part_cost;

        return $totalPurchaseCost;
    }

    public function getTotalRepairCostAttribute(){

        $repairCost=0;

        if(count($this->repair_item)){

            foreach ($this->repair_item as $repair){
                if($repair->type===RepairsItems::TYPE_EXTERNAL){
                    $repairCost=$repair->actual_repair_cost<1 ? $repair->estimate_repair_cost:$repair->actual_repair_cost;
                }

            }

        }


        $totalRepairCost=$repairCost + $this->part_cost;
        return $totalRepairCost;

    }

    public function repair_item(){
        return $this->hasMany('App\RepairsItems','stock_id','id');
    }

    public function getTotalCostsFormattedAttribute()
    {
        return money_format(config('app.money_format'), $this->total_costs);
    }

    public function getTotalCostWithRepairFormattedAttribute(){
        return money_format(config('app.money_format'), $this->total_cost_with_repair);
    }

    public function getOrderhubNewSkuAttribute()
    {
        $newSku = '';

        if (strtolower($this->make) == 'apple' && strpos(strtolower($this->name), 'iphone') !== false) {
            // pattern [model] - [colour] - [capacity] - [network] - [grade] (IP6SPLUS-SG-64-UN-A)
            $pattern = [
                'model' => $this->getOrderhubNewSkuModelAttribute(),
                'colour' => $this->getOrderhubNewSkuColourAttribute(),
                'capacity' => $this->capacity,
                'network' => $this->getOrderhubNewSkuNetworkAttribute(),
                'grade' => $this->getOrderhubNewSkuConditionAttribute()
            ];

            foreach ($pattern as $key => $val) {
                if (!$val) return $newSku;
            }
            $newSku = implode("-", $pattern);
            return $newSku;
        }

        return $newSku;
    }

    public function getOrderhubNewSkuNetworkAttribute()
    {
        $network = '';

        $networks = [
            'unlocked' => 'UN',
            'ee' => 'EE',
            'o2' => 'O2',
            'vodafone' => 'VODA'
        ];

        if (in_array(strtolower($this->network), array_keys($networks))) {
            $network = $networks[strtolower($this->network)];
        }

        return $network;
    }

    public function getOrderhubNewSkuColourAttribute()
    {
        $colour = '';

        $colours = [
            'rose gold' => 'RG',
            'black' => 'BK',
            'silver' => 'SV',
            'gold' => 'GL',
            'jet black' => 'JB',
            'red' => 'RED',
            'space grey' => 'SG',
            'space gray' => 'SG',
        ];

        if (in_array(strtolower($this->colour), array_keys($colours))) {
            $colour = $colours[strtolower($this->colour)];
        }

        return $colour;
    }

    public function getOrderhubNewSkuModelAttribute()
    {
        $model = '';

        $name = explode(" ", $this->name);

        if (count($name) >= 2) {
            if (strtolower($name[0]) == 'iphone') {
                $modelParts = $name;
                $modelParts[0] = "IP";
                $model = strtoupper(implode("", $modelParts));
                $model = str_replace(" ", "", str_replace("+", "PLUS", $model));
            }
        }

        return $model;
    }

    public function getOrderhubNewSkuConditionAttribute()
    {
        $condition = '';

        if ($this->grade == self::GRADE_FULLY_WORKING_NO_TOUCH_ID) {
            $condition = "NTID";
        } else {
            $condition = $this->condition;
        }

        return $condition;
    }

    public function getPhoneCheckUpdatesAttribute()
    {
        $updates = 0;

        if ($this->phone_check) {
            $updates = $this->phone_check->no_updates;
        }

        return $updates;
    }

    public function getCustomerIdAttribute()
    {
        if ($this->sale_id) {
            if(isset($this->sale->user)){

                return $this->sale->user->invoice_api_id;
            }

        }
    }

    public function getCustomerNameAttribute()
    {
        if ($this->sale_id) {
            if(isset($this->sale->user)){
                return $this->sale->user->full_name;
            }

        }
    }

    public function getSupplierNameAttribute()
    {
        if ($this->supplier_id) {
            return $this->supplier->name;
        }
    }

    public function getModelAttribute()
    {
        if ($this->product_id) {
            return $this->product->model;
        }
    }

    public function getBuyersRefAttribute()
    {
        if ($this->sale_id) {
            if(isset($this->sale->buyers_ref)){
                return $this->sale->buyers_ref;
            }

        }
    }

    public function getUnlockedFromNetworkAttribute()
    {
        if ($this->unlock()->where('status', Unlock::STATUS_UNLOCKED)->count()) {
            return $this->unlock->network;
        }
    }

    public function getRepairsAndParts()
    {
        $repairs = $this->repairs()->orderBy('repair_id', 'asc')->get();
        $repairsWithParts = [
            "Repair 1" => ['date' => '', 'parts' => '', 'retest' => ''],
            "Repair 2" => ['date' => '', 'parts' => '', 'retest' => ''],
            "Repair 3" => ['date' => '', 'parts' => '', 'retest' => '']
        ];
        if (count($repairs)) {
            foreach ($this->repairs as $key => $repair) {
                $repairsWithParts["Repair " . ++$key] = ['date' => $repair->created_at->format('d/m/Y'), 'parts' => $repair->parts, 'retest' => $this->checkPhoneCheckResultsDate($repair->created_at)];
            }
        }
        //dd($repairsWithParts);
        return $repairsWithParts;
    }

    public function checkPhoneCheckResultsDate($date)
    {
        if (!$this->phone_check()->count()) {
            return "No Report";
        }
        //return $date;
        return "[not available yet]";
        // [] = ['date' => '', 'working' => '', 'failed' => '', 'passed' => '']
        $phoneCheckLogs = [];
        $firstPhoneCheckLog = $this->stockLogs()->where('content', 'like', '{"TransactionID"%')->first();
        $phoneCheckLogs[] = [
            'date' => $firstPhoneCheckLog->created_at->format('d/m/y H:i:s'),
            'working' => json_decode($firstPhoneCheckLog->content)->Working,
            'failed' => json_decode($firstPhoneCheckLog->content)->Failed,
            'passed' => json_decode($firstPhoneCheckLog->content)->Passed
        ];
        $logs = $this->stockLogs()->where('content', 'like', "Changes%")->where(function ($q) {
            $q->where('content', 'like', "%Working%");
            $q->orWhere('content', 'like', "%Passed%");
            $q->orWhere('content', 'like', "%Failed%");
        })->orderBy('id', 'asc')->get();
        foreach ($logs as $n => $log) {
            $working = "";
            $passed = "";
            $failed = "";
            if (strpos($log->content, "<b>Working</b>") !== false) {
                if (preg_match('/<b>Working<\/b>:\s+(?<workingMatch>.*?)(\.|\s*<br\/>)/', $log->content, $workingMatch) && isset($workingMatch['workingMatch'])) {
                    $working = $workingMatch['workingMatch'];
                }
            }
            if (strpos($log->content, "<b>Passed</b>") !== false) {
                if (preg_match('/<b>Passed<\/b>:\s+(?<passedMatch>.*?)(\.|\s*<br\/>)/', $log->content, $passedMatch) && isset($passedMatch['passedMatch'])) {
                    $passed = $passedMatch['passedMatch'];
                }
            }
            if (strpos($log->content, "<b>Failed</b>") !== false) {

                if (preg_match('/<b>Failed<\/b>:\s+(?<failedMatch>.*?)(\.|\s*<br\/>)/', $log->content, $failedMatch) && isset($failedMatch['failedMatch'])) {
                    $failed = $failedMatch['failedMatch'];
                }
            }

            // if not found, assign previous
            if (!$working && isset($phoneCheckLogs[0]['working'])) {
                $working = $phoneCheckLogs[0]['working'];
            }
            if (!$passed && isset($phoneCheckLogs[0]['passed'])) {
                $passed = $phoneCheckLogs[0]['passed'];
            }
            if (!$failed && isset($phoneCheckLogs[0]['failed'])) {
                $failed = $phoneCheckLogs[0]['failed'];
            }

            $phoneCheckLogs[] = [
                'date' => $log->created_at->format('d/m/y H:i:s'),
                'working' => $working,
                'failed' => $failed,
                'passed' => $passed,
                'content' => $log->content
            ];
        }
        return $phoneCheckLogs;
        dd($phoneCheckLogs, $logs->lists('created_at', 'content'));
    }

    public function getRepair1DateAttribute()
    {
        try {
            return $this->getRepairsAndParts()['Repair 1']['date'];
        } catch (Exception $e) {
            return "error";
        }
    }

    public function getRepair1PartsAttribute()
    {
        try {
            return $this->getRepairsAndParts()['Repair 1']['parts'];
        } catch (Exception $e) {
            return "error";
        }
    }

    public function getRepair1RetestAttribute()
    {
        try {
            return $this->getRepairsAndParts()['Repair 1']['retest'];
        } catch (Exception $e) {
            return "error";
        }
    }

    public function getRepair2DateAttribute()
    {
        try {
            return $this->getRepairsAndParts()['Repair 2']['date'];
        } catch (Exception $e) {
            return "error";
        }
    }

    public function getRepair2PartsAttribute()
    {
        try {
            return $this->getRepairsAndParts()['Repair 2']['parts'];
        } catch (Exception $e) {
            return "error";
        }
    }

    public function getRepair2RetestAttribute()
    {
        try {
            return $this->getRepairsAndParts()['Repair 2']['retest'];
        } catch (Exception $e) {
            return "error";
        }
    }

    public function getRepair3DateAttribute()
    {
        try {
            return $this->getRepairsAndParts()['Repair 3']['date'];
        } catch (Exception $e) {
            return "error";
        }
    }

    public function getRepair3PartsAttribute()
    {
        try {
            return $this->getRepairsAndParts()['Repair 3']['parts'];
        } catch (Exception $e) {
            return "error";
        }
    }

    public function getRepair3RetestAttribute()
    {
        try {
            return $this->getRepairsAndParts()['Repair 3']['retest'];
        } catch (Exception $e) {
            return "error";
        }
    }

    public function isValidForReturn($date = null)
    {
        $date ?: Carbon::now();
        if ($this->grade == self::GRADE_FULLY_WORKING && $this->sale && $this->sale->created_at->diffInMonths($date) < 6) {
            return true;
        } elseif ($this->grade != self::GRADE_FULLY_WORKING && $this->sale && $this->sale->created_at->diffInDays($date) <= 14) {
            return true;
        }

        return false;
    }

    public function setSalePriceAttribute($value)
    {
//        if (
//            ($this->getOriginal('sale_id') && $this->sale_id) &&
//            $this->getOriginal('sale_price') != $value &&
//            ($this->sale && (!$this->sale->other_recycler && $this->sale->invoice_status != Invoice::STATUS_OPEN))
//            && ((Auth::user() && !in_array(Auth::user()->email, ['victoria@recomm.co.uk'])) || !Auth::user())
//        ) {
//            throw new Exception("Item already sold, can't change the sales price.");
//        }

        $this->attributes['sale_price'] = $value;
    }

    public function setPurchaseDateAttribute($value)
    {
        if (strpos($value, '/') !== false) {
            // Change two-digit year.
            $value = preg_replace('#/(\d\d(?: |$))#', '/20$1', $value);
            // Make standard date.
            $regex = '#^(\d\d)/(\d\d)/(\d\d\d\d)(.*)#';
            preg_match($regex, $value, $datePartsMatch);
            $value = preg_replace($regex, '$3-$2-$1$4', $value);
        }

        if (strlen($value) === 16) { // Date and time without seconds.
            $value .= ':00';
        }

        $this->attributes['purchase_date'] = $value ? $this->fromDateTime($value) : '';
    }

    public function save(array $options = array())
    {
        if (!$this->imei && preg_match('/^\d{15,16}$/', $this->serial)) {
            $this->imei = $this->serial;
            $this->serial = '';
        }

        if (!$this->manual_sku || !isset($options['avoid_sku_update'])) {
            if (!$this->exists || !empty($options['force_sku_rewrite'])) {
                $this->sku = $this->getSkuAttribute();
            } else {
                if (!in_array($this->shown_to, [Stock::SHOWN_TO_EBAY, Stock::SHOWN_TO_EBAY_AND_SHOP])) {
                    foreach (self::$skuAttributes as $attrName) {
                        if ($this->getOriginal($attrName) != $this->$attrName) {
                            $this->sku = $this->getSkuAttribute();
                            break;
                        }
                    }
                }
            }
        }

        // option 'phone_check_save', so it won't set manual_notes if it's saved in phonecheck cron
        if (!isset($options['phone_check_save'])) {
            if ($this->notes != $this->getOriginal('notes') && strlen($this->notes) > 1 && !$this->manual_notes) {
                $this->manual_notes = 1;
            } elseif ($this->notes != $this->getOriginal('notes') && strlen($this->notes) == 0 && $this->manual_notes) {
                $this->manual_notes = 0;
            }
        }

        //update margin
        if ($this->sale_price > 0) {

            if($this->vat_type==="Standard"){
                $this->margin = $this->total_price_ex_vat ? round((($this->total_price_ex_vat - $this->total_cost_with_repair) / $this->total_price_ex_vat * 100), 2):0;
            }else{
                $this->margin =$this->sale_price ? round((($this->sale_price - $this->total_cost_with_repair) / $this->sale_price * 100), 2):0;
            }
        }

        if ($this->sale_price < $this->purchase_price && $this->status == Stock::STATUS_IN_STOCK && !in_array($this->shown_to, [Stock::SHOWN_TO_EBAY, Stock::SHOWN_TO_EBAY_AND_SHOP, Stock::SHOWN_TO_EBAY_AUCTION])) {
            $this->shown_to = Stock::SHOWN_TO_NONE;
        }

        $statuses = self::getAvailableStatuses();
        if (!$this->status || !in_array($this->status, $statuses)) {
            $this->status = Stock::STATUS_INBOUND;
        }

        if ($this->status == self::STATUS_SOLD && $this->getOriginal('status') != self::STATUS_SOLD) {
            $this->shown_to = Stock::SHOWN_TO_NONE;
            $user = Auth::user() ? Auth::user()->id : null;
            $content = "Item Sold. Status Changed from " . $this->getOriginal('status') . " to " . $this->status;
            StockLog::create(['stock_id' => $this->id, 'content' => $content, 'user_id' => $user]);
        }

        if ($this->status != self::STATUS_LOST && $this->getOriginal('status') == self::STATUS_LOST) {
            $this->marked_as_lost = null;
            $this->lost_reason = "";
        }

        if ($this->sale_id == null && $this->getOriginal('sale_id') != null) {
            $user = Auth::user() ? Auth::user()->id : null;
            $content = "Item Removed from Sale. Status Changed from " . $this->getOriginal('status') . " to " . $this->status;
            StockLog::create(['stock_id' => $this->id, 'content' => $content, 'user_id' => $user]);
        }

        if ($this->sale_id != null && $this->getOriginal('sale_id') != $this->sale_id && $this->unlock) {
            if (in_array($this->unlock->status, [Unlock::STATUS_PROCESSING, Unlock::STATUS_NEW, Unlock::STATUS_REPROCESSING, Unlock::STATUS_FAILED, Unlock::STATUS_UNLOCKED])) {
                if ($this->unlock->user_id != $this->sale->user_id) {
                    $this->unlock->user_id = $this->sale->user_id;
                    $this->unlock->save();
                    $content = "Item Sold, Assigned Processing Unlock to Customer";
                    StockLog::create(['stock_id' => $this->id, 'content' => $content]);
                }
            }
        }

        if ($this->network == "Unlocked" && $this->getOriginal('network') != $this->network) {
            StockLog::create(['stock_id' => $this->id, 'content' => 'Network Changed to Unlocked']);
        }

        parent::save($options);
    }

    public static function getDataByIMEI($imei)
    {
        return Stock::where('imei', $imei)->first();
    }

    public static function getMake()
    {

        $makeList = Stock::where('make','!=',"")->select('make')->groupBy('make')->get();

        return $makeList;
    }

    public static function getCustomerId(){
        $customerId = User::select('id')->where('invoice_api_id','!=','')->get();

        return $customerId;
    }
    public static function getCustomerName(){
        $customerName= User::select('id','first_name','last_name')->where('invoice_api_id','!=','')->get();
        return $customerName;
    }

    public static function getSupplier(){
        $supplierList = Supplier::select('name')->get();
        return $supplierList;

    }

    public static function getProductType(){

        $productType = Stock::where('product_type','!=',"")->select('product_type')->groupBy('product_type')->get();

        return $productType;
    }
    public static function getProduct(){

        $productAll = Product::where('non_serialised',0)->select(['id','product_name','slug'])->get();

        return $productAll;
    }

    public static function getProductNonSerialised(){

        $productAll = Product::where('non_serialised',1)->select(['id','product_name','slug'])->get();

        return $productAll;
    }

    public function phoneCheckReports(){
        return $this->hasOne('App\PhoneCheckReports','stock_id','id');

    }

    public function processingImage(){
        return $this->hasMany('App\ImageProcessing','stock_id','id');
    }

}
