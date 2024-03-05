<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Address;
use App\Models\BillingAddress;
use App\Models\Country;
use App\Models\Customer;

use App\Models\Database\Scopes\ValueEquals;
use App\Models\Invoice;
use App\Models\Unlock\Order;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\UserDocument;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    const LOCATION_UK = 'UK';
    const LOCATION_EUROPE = 'Europe';
    const LOCATION_WORLD = 'Outside of Europe';

    const HEARD_ABOUT_US_LINKEDIN = "LinkedIn";
    const HEARD_ABOUT_US_GOOGLE = "Google";
    const HEARD_ABOUT_US_RECOMMENDATION = "Recommendation";
    const HEARD_ABOUT_US_OTHER = "Other";

    const CUSTOMER_TYPE_RETAIL_SHOP = "Retail Shop";
    const CUSTOMER_TYPE_WHOLESEALER = "Wholesaler";
    const CUSTOMER_TYPE_ONLINE_SELLER = "Online Seller";
    const CUSTOMER_TYPE_INSURANCE = "Insurance";
    const CUSTOMER_TYPE_OTHER = "Other";

    const ADMIN_TYPE_MANAGER = "manager";
    const ADMIN_TYPE_STAFF = "staff";
    const ADMIN_TYPE_ADMIN = "admin";

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

    protected $fillable = [
        'type', 'customer_type', 'first_name', 'last_name', 'email', 'password', 'business_description', 'company_name', 'devices_per_week',
        'phone', 'stock_fully_working', 'stock_major_fault', 'stock_minor_fault', 'stock_no_power', 'stock_icloud_locked',
        'vat_registered', 'whatsapp', 'whatsapp_added', 'marketing_emails_subscribe', 'location', 'heard_about_us', 'station_id','quickbooks_customer_category','sell_to_recomm','received','kyc_verification'
        ,'vat_types','processing_data','supplier_id','processing_price','purchase_from_us'
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $dates = ['deleted_at', 'balance_due_date'];



    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public static function ownsImei($userId, $imei)
    {
        /** @var \App\User $user */
        $user = Auth::user() && Auth::user()->id == $userId ? Auth::user() : self::find($userId);
        $stockItem = Stock::where('imei', $imei)->first();

        if (!$user || !$stockItem || !$stockItem->sale_id) {
            return false;
        }

        return in_array($stockItem->sale_id, $user->sales()->lists('id'));
    }

    public function scopeWithUnregistered()
    {
        return (new static)->newQueryWithoutScope(new ValueEquals('registered', true));
    }

    public function scopeUnregistered()
    {
        return (new static)->withUnregistered()->where('registered', false);
    }

    protected static function boot()
    {
        self::addGlobalScope(new ValueEquals('registered', true));
        parent::boot();
    }

    public function canRead($resourceNames, $instance = null, $combineAsAnd = false)
    {
        if (!is_array($resourceNames)) $resourceNames = [$resourceNames];

        $canCombined = false;

        foreach ($resourceNames as $resourceName) {
            switch ($resourceName) {
                case 'stock.all':
                    $can = $this->type !== 'user';
                    break;
                case 'stock.notes':
                    $can = $this->type !== 'user' || $instance && $instance->grade === Stock::GRADE_MAJOR_FAULT;
                    break;
                case 'stock.serial':
                case 'stock.photos':
                case 'stock.inbound':
                case 'stock.condition':
                case 'stock.lcd_status':
                    $can = $this->type !== 'user';
                    break;
                default:
                    $can = false;
            }
            $canCombined = $combineAsAnd ? ($canCombined && $can) : ($canCombined || $can);
        }

        return $canCombined;
    }

    public function canWrite($resourceNames, $instance = null, $combineAsAnd = false)
    {
        if (!is_array($resourceNames)) $resourceNames = [$resourceNames];

        $canCombined = false;

        foreach ($resourceNames as $resourceName) {
            switch ($resourceName) {
                case 'stock.in_repair':
                    $can = $this->type !== 'user';
                    break;
                default:
                    $can = false;
            }
            $canCombined = $combineAsAnd ? ($canCombined && $can) : ($canCombined || $can);
        }

        return $canCombined;
    }

    public function canPayForSale(Sale $sale)
    {
        return $sale->invoice_status === Invoice::STATUS_OPEN && $this->type === 'user';
    }

    public function canDeleteSale(Sale $sale)
    {
        return in_array($this->type, ['manager', 'admin']);
    }

    public function canVoidSale(Sale $sale)
    {
        if ($this->type === 'user') {
            return $sale->invoice_status === Invoice::STATUS_OPEN;
        }
        else {
            return $sale->invoice_status !== Invoice::STATUS_VOIDED;
        }
    }

    /**
     * @return Customer
     */
    public function getCustomer()
    {

        $customer = new \App\Models\Customer([
            'external_id' => $this->invoice_api_id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            //'company_name' => $this->company_name,
            'phone' => $this->phone,
        ]);

        if ($this->address) {
            $customer->shipping_address = new \App\Models\Address([
                'line1' => $this->address->line1,
                'line2' => $this->address->line2,
                'city' => $this->address->city,
                'county' => $this->address->county,
                'postcode' => $this->address->postcode,
                'country' => $this->address->country,
            ]);
        }
        if($this->billingAddress){
            $customer->billing_address = new \App\Models\BillingAddress([
                'line1' => $this->billingAddress->line1,
                'line2' => $this->billingAddress->line2,
                'city' => $this->billingAddress->city,
                'county' => $this->billingAddress->county,
                'postcode' => $this->billingAddress->postcode,
                'country' => $this->billingAddress->country,
            ]);
        }

        return $customer;
    }

    public function document(){
        return $this->hasOne(UserDocument::class,'user_id','id');
    }

    public function unlock_orders()
    {
        return $this->hasMany(Order::class);
    }

    public function unlocks()
    {
        return $this->hasMany('App\Unlock');
    }

    public function pre_orders()
    {
        return $this->hasMany('App\PreOrder');
    }

    public function basket()
    {
        return $this->belongsToMany(\App\Models\Stock::class, "baskets")->withPivot('created_at');
    }

    public function address()
    {
        return $this->hasOne(\App\Models\User\Address::class);
    }

    public function billingAddress()
    {
        return $this->hasOne(\App\Models\User\BillingAddress::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function getSalesCountAttribute()
    {
        return $this->sales()->count();
    }

    public function emailTracking()
    {
        return $this->hasMany(EmailTracking::class);
    }

    public function googleEmails()
    {
        return $this->hasMany('App\GoogleEmail');
    }

    public function googleEmailChecks()
    {
        return $this->hasMany('App\GoogleEmailCheck');
    }

    public function stockLogs()
    {
        return $this->hasMany(StockLog::class);
    }

    public function phone_checks()
    {
        return $this->hasMany(PhoneCheck::class, 'station_id', 'station_id');
    }

    public function stock_returns()
    {
        return $this->hasMany(StockReturn::class, 'user_id', 'id');
    }

    public function getHasIncorrectCountryAttribute()
    {
        static $acceptedCountryNames;
        if (!$acceptedCountryNames) $acceptedCountryNames = Country::lists('name');

        return $this->type === 'user' &&
            (!$this->address || !in_array($this->address->country, $acceptedCountryNames));
    }

    public function getAllowedGradesAttribute()
    {
        $allowedGrades = [];

        if ($this->stock_fully_working) $allowedGrades[] = Stock::GRADE_FULLY_WORKING;
        if ($this->stock_minor_fault)   $allowedGrades[] = Stock::GRADE_MINOR_FAULT;
        if ($this->stock_major_fault)   $allowedGrades[] = Stock::GRADE_MAJOR_FAULT;
        if ($this->stock_no_power)      $allowedGrades[] = Stock::GRADE_BROKEN;
        if ($this->stock_icloud_locked) $allowedGrades[] = Stock::GRADE_LOCKED;

        return $allowedGrades;
    }

    public function getAllowedStatusesViewingAttribute()
    {
        if ($this->canRead('stock.all')) {
            return Stock::getAvailableStatuses();
        }
        elseif ($this->canRead('stock.inbound')) {
            return [Stock::STATUS_INBOUND, Stock::STATUS_IN_STOCK];
        }
        else {
            return [Stock::STATUS_IN_STOCK];
        }
    }

    public function getAllowedStatusesBuyingAttribute()
    {
        if ($this->canRead('stock.inbound')) {
            return [Stock::STATUS_INBOUND, Stock::STATUS_IN_STOCK, Stock::STATUS_READY_FOR_SALE,Stock::STATUS_BATCH,Stock::STATUS_ALLOCATED,Stock::STATUS_RETAIL_STOCK];
        }
        else {
            return [Stock::STATUS_IN_STOCK, Stock::STATUS_READY_FOR_SALE,Stock::STATUS_RETAIL_STOCK];
        }
    }

    public function getFirstNameAttribute($value)
    {
        $value = trim($value);

        // Fix for when name is written in all caps.
        if (!preg_match('/[a-z]/', $value)) {
            $value = ucfirst(mb_strtolower($value));
        }

        // Fix for some rather unusual situations, for instance when someone put "Mr" as their first name and "Sid" as
        // their last name.
        if (is_honorific($value) && isset($this->attributes['last_name'])) {
            $value = $value . ' ' . $this->attributes['last_name'];
        }

        return ucfirst($value);
    }

    public function getLastNameAttribute($value)
    {
        if (isset($this->attributes['first_name']) && is_honorific($this->attributes['first_name'])) {
            return '';
        }

        $value = trim($value);

        // Fix for when name is written in all caps.
        if (!preg_match('/[a-z]/', $value)) {
            $value = ucfirst(mb_strtolower($value));
        }

        return ucfirst($value);
    }

    public function getBalanceAttribute()
    {
        if (!$this->invoice_api_id) return 0;

        $invoicing = app('App\Contracts\Invoicing');
        $invoicing->setCacheTime(0);
        $customer = $invoicing->getCustomer($this->invoice_api_id);

        return -$customer->balance - $this->balance_spent;
    }

    public function getBalanceFormattedAttribute()
    {
        return money_format(config('app.money_format'), $this->balance);
    }

    /**
     * Get user-specific texts shown in various places on the site.
     * @return array
     */
    public function getTextsAttribute()
    {
        switch ($this->type) {
            case 'user':
                return [
                    'sales' => [
                        'entity' => 'order',
                        'title' => 'My Orders',
                        'create' => 'Create order',
                    ]
                ];
            default:
                return [
                    'sales' => [
                        'entity' => 'sale',
                        'title' => 'Sales',
                        'create' => 'Create sale',
                    ]
                ];
        }
    }

    public function getFullNameAttribute()
    {
        // If we have a title instead of name in the first name then we assume the first name is in the last name field.
        // So we return nothing here to avoid duplicate. For instance { first_name: "Ms", last_name: "Jones" } will
        // yield
        if (is_honorific($this->attributes['first_name'])) {
            return $this->first_name;
        }
        else {
            return $this->first_name . ($this->last_name ? ' ' : '') . $this->last_name;
        }
    }

    public function getHashAttribute()
    {
        $hash = substr(md5($this->id.$this->email), 0, 32);

        return $hash;
    }

    public function getNotesShortAttribute()
    {
        return strlen($this->notes) > 20 ? substr($this->notes,0,20)."..." : $this->notes;
    }

    public static function getAvailableLocations()
    {
        return [self::LOCATION_UK, self::LOCATION_EUROPE, self::LOCATION_WORLD];
    }

    public static function getAvailableLocationsWithKeys()
    {
        return array_combine(self::getAvailableLocations(), self::getAvailableLocations());
    }

    public static function getAvailableHeardAboutUs()
    {
        return [self::HEARD_ABOUT_US_LINKEDIN, self::HEARD_ABOUT_US_GOOGLE, self::HEARD_ABOUT_US_RECOMMENDATION, self::HEARD_ABOUT_US_OTHER];
    }

    public static function getAvailableHeardAboutUsWithKeys()
    {
        return array_combine(self::getAvailableHeardAboutUs(), self::getAvailableHeardAboutUs());
    }

    public static function getAvailableCustomerTypes()
    {
        return [self::CUSTOMER_TYPE_RETAIL_SHOP, self::CUSTOMER_TYPE_WHOLESEALER, self::CUSTOMER_TYPE_ONLINE_SELLER, self::CUSTOMER_TYPE_INSURANCE, self::CUSTOMER_TYPE_OTHER];
    }

    public static function getAvailableCustomerTypesWithKeys()
    {
        return array_combine(self::getAvailableCustomerTypes(), self::getAvailableCustomerTypes());
    }

    public static function getAvailableAdminTypes()
    {
        return [self::ADMIN_TYPE_STAFF, self::ADMIN_TYPE_MANAGER, self::ADMIN_TYPE_ADMIN];
    }

    public static function getAvailableAdminTypesWithKeys()
    {
        return array_combine(
            self::getAvailableAdminTypes(),
            array_map('ucfirst', self::getAvailableAdminTypes())
        );
    }




}
