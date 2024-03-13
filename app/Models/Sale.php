<?php

namespace App\Models;

use App\Commands\Sales\EmailSend;
use App\Models\Invoice;
use App\Models\SaleLog;
use App\Models\Stock;
use App\Models\User;
use App\Models\NewSalesStock;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Auth;

class Sale extends Model
{
    use HasFactory, SoftDeletes;
    const MINIMUM_ORDER_AMOUNT = 1000;

    const PAYMENT_WAIT_HOURS = 1;

    const COURIER_UK_MAIL = "UK Mail";
    const COURIER_UPS = "UPS";
    const COURIER_ROYAL_MAIL = "Royal Mail";
    const COURIER_TNT = "TNT";
    const COURIER_DPD_LOCAL = "DPD Local";
    const COURIER_FEDEX = "FedEx";
    const COURIER_DHL = "DHL";
    const COURIER_CUSTOMER_COLLECTED = "Customer Collected";
    const COURIER_AMAZON_LOGISTIC = "Amazon Logistic";
    const COURIER_DPD = "DPD";

    const PLATFORM_EBAY="eBay";
    const PLATFORM_RECOMMERCE="Recomm";
    const PLATFORM_BACKMARKET="Backmarket";



    const DEFAULT_BOX_ACCESSORIES_COST_EX_VAT_EBAY=2.50;

    const PAYMENT_METHOD_CARD = "Card";
    const PAYMENT_METHOD_CASH = "Cash";

    const VAT_TYPE_MARGIN = "VAT Margin";
    const VAT_TYPE_20 = "20.0% S";
    const VAT_TYPE_0 = "0.0% ECS";

    const INVOICE_TYPE_INVOICE_CREATE = "InvoiceCreate";
    const INVOICE_TYPE_INVOICE_CUSTOM_ORDER_CREATE = "InvoiceCustomOrderCreate";
    /*const INVOICE_TYPE_INVOICE_EBAY_CREATE = "InvoiceEbayCreate";
    const INVOICE_TYPE_INVOICE_EPOS_CREATE = "InvoiceEposCreate";
    const INVOICE_TYPE_INVOICE_MIGHTY_DEALS_CREATE = "InvoiceMightyDealsCreate";
    const INVOICE_TYPE_INVOICE_ORDERHUB_CREATE = "InvoiceOrderhubCreate";*/

    const SOURCE_ADMIN_RECOMMERCETECH = "admin.recommercetech";
    const SOURCE_RECOMMERCETECH = "recommercetech";

    protected $table = 'new_sales';

    protected $dates = ['reminder_sent_at'];

    protected $casts = ['invoice_details' => 'object'];

    protected $logChanges = true;

    public function scopeStatus(Builder $query, $value)
    {
        if ($value) {
            $query->where('invoice_status', $value);
        }
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function stock()
    {
        return $this->belongsToMany(Stock::class, 'new_sales_stock');
    }

    public function newSalesStock(){
        return $this->hasOne(NewSalesStock::class,'sale_id','id');
    }

    public function ebay_orders()
    {
        return $this->hasMany(EbayOrders::class,'new_sale_id','id');
    }

    /*public function parts()
    {
        return $this->hasMany('App\SalePart');
    }



    public function orderhub_orders()
    {
        return $this->hasMany('App\OrderhubOrder');
    }

    public function mighty_deals_order()
    {
        return $this->hasOne('App\MightyDealOrder');
    }*/

    public function created_by_user()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function sale_logs()
    {
        return $this->hasMany(SaleLog::class);
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function getPaidAttribute()
    {
        return in_array(
            $this->invoice_status,
            [Invoice::STATUS_PAID, Invoice::STATUS_READY_FOR_DISPATCH, Invoice::STATUS_DISPATCHED]
        );
    }

    public function getPaymentDeadlineAttribute()
    {
        $date = clone $this->created_at;
        $date->addHours(self::PAYMENT_WAIT_HOURS);
        return $date;
    }

    public function getAmountAttribute()
    {
        return (float) $this->invoice_total_amount;
    }

    public function getAmountFormattedAttribute()
    {
        return money_format($this->amount);

    }

    /**
     * Alternative version of the invoice status - how it has to be shown to the user. Only use for display.
     * @return string
     */
    public function getInvoiceStatusAltAttribute()
    {
        $status = $this->invoice_status;

        switch ($status) {
            case Invoice::STATUS_OPEN:
                return 'awaiting payment';
        }

        return $status;
    }

    /**
     * Alternative version of the invoice creation status - how it has to be shown to the user. Only use for display.
     * @return string
     */
    public function getInvoiceCreationStatusAltAttribute()
    {
        $status = $this->invoice_creation_status;

        switch ($status) {
            case Invoice::CREATION_STATUS_NOT_INITIALISED:
                return 'initialising';
            case Invoice::CREATION_STATUS_SUCCESS:
                return 'created';
            case Invoice::CREATION_STATUS_NOT_INITIALISED:
                return 'initialising';
        }

        return $status;
    }

    /**
     * Does the invoice status indicate that the invoice processing was finished, regardless of whether with success
     * or error?
     * @return bool
     */
    public function getInvoiceCreationStatusFinishedAttribute()
    {
        return !in_array(
            $this->invoice_creation_status, [Invoice::CREATION_STATUS_NOT_INITIALISED, Invoice::CREATION_STATUS_IN_PROGRESS]
        );
    }

    public function getProfitRatioAttribute()
    {
        $profit = "";
        if($this->stock->sum('sale_price') > 0) {
            $profit = number_format(($this->stock->sum('sale_price')-$this->stock->sum('total_costs'))/$this->stock->sum('sale_price')*100, 2)."%";
        }
        return $profit;
    }

    public function getProfitAmountAttribute()
    {
        return $this->stock->sum('sale_price') - $this->stock->sum('total_costs');
    }

    public function getProfitAmountFormattedAttribute()
    {
        $profit = "";
        if($this->stock->sum('total_costs') > 0) {
            $profit = money_format($this->stock->sum('sale_price')-$this->stock->sum('total_costs'));

        }
        return $profit;
    }


    public function getAmountPaidFormattedAttribute()
    {
        return money_format($this->amount_paid);
    }

    public function getChangeDueAttribute()
    {
        return $this->amount_paid - $this->invoice_total_amount;
    }

    public function getChangeDueFormattedAttribute()
    {
        return money_format($this->change_due);

    }

    public static function getAvailablePaymentMethods()
    {
        return [self::PAYMENT_METHOD_CARD, self::PAYMENT_METHOD_CASH];
    }

    public static function getAvailablePaymentMethodsWithKeys()
    {
        return array_combine(self::getAvailablePaymentMethods(), self::getAvailablePaymentMethods());
    }

    public static function getAvailableCouriers()
    {
        return [self::COURIER_UK_MAIL, self::COURIER_UPS, self::COURIER_ROYAL_MAIL, self::COURIER_TNT, self::COURIER_DPD_LOCAL, self::COURIER_FEDEX, self::COURIER_DHL, self::COURIER_AMAZON_LOGISTIC, self::COURIER_DPD, self::COURIER_CUSTOMER_COLLECTED];
    }

    public static function getAvailableCouriersWithKeys()
    {
        return array_combine(self::getAvailableCouriers(), self::getAvailableCouriers());
    }

    public static function getAvailableVatTypes()
    {
        return [self::VAT_TYPE_MARGIN, self::VAT_TYPE_20, self::VAT_TYPE_0];
    }

    public static function getAvailableVatTypesWithKeys()
    {
        return array_combine(self::getAvailableVatTypes(), self::getAvailableVatTypes());
    }

    public function getCourierWebsiteAttribute()
    {
        if(in_array($this->courier, self::getAvailableCouriers())) {
            switch($this->courier) {
                case self::COURIER_UK_MAIL:
                    return "https://www.ukmail.com/manage-my-delivery/manage-my-delivery";
                case self::COURIER_UPS:
                    return "https://www.ups.com/WebTracking/track?loc=en_GB";
                case self::COURIER_ROYAL_MAIL:
                    return "https://www.royalmail.com/track-your-item";
                case self::COURIER_TNT:
                    return "https://www.tnt.com/express/en_gb/site/shipping-tools/tracking.html";
                case self::COURIER_DPD_LOCAL:
                    return "http://www.dpdlocal.co.uk/content/how-can-we-help/index.jsp";
                case self::COURIER_FEDEX:
                    return "www.fedex.com/gb/track/";
                case self::COURIER_DHL:
                    return "www.dhl.co.uk/en/express/tracking.html";
                case self::COURIER_DPD:
                    return "http://www.dpd.co.uk/";
                case self::COURIER_CUSTOMER_COLLECTED:
                    return "";
            }
        } else {
            if($this->user->location == User::LOCATION_UK)
                return "https://www.ukmail.com/manage-my-delivery/manage-my-delivery";
            else
                return "https://www.tnt.com/express/en_gb/site/shipping-tools/tracking.html";
        }
    }

    public function getDeviceLockedAttribute()
    {
        if(count($this->stock)){
            foreach($this->stock as $stock) {
                if($stock->stockLogs->contains('content', 'This device has an iCloud account'))
                    return true;
            }
        }

        return false;
    }

    public function save(array $options = array())
    {
        if (
            $this->invoice_status === Invoice::STATUS_VOIDED &&
            $this->getOriginal('invoice_status') !== Invoice::STATUS_VOIDED
        ) {
            $this->returnItemsToStock();

            $this->batch_id = null;
        }
        elseif (
            $this->invoice_status === Invoice::STATUS_PAID &&
            $this->getOriginal('invoice_status') === Invoice::STATUS_OPEN
        ) {
            Queue::pushOn('emails', new EmailSend($this, EmailSend::EMAIL_PAID));
            foreach ($this->stock as $item) {
                $item->status = Stock::STATUS_PAID;
                $item->save();
            }
        }

        try {
            if ($this->exists && $this->isDirty()) {
                $changes = "";
                foreach ($this->getAttributes() as $key => $value) {
                    if ($value !== $this->getOriginal($key) && !checkUpdatedFields($value, $this->getOriginal($key))) {
                        if(strpos($value,'error')){
                            $value='something went wrong';
                        }
                        if(!is_null($this->getOriginal($key))){
                            if($this->getOriginal($key)!=='updated_at' || $this->getOriginal($key)!=='created_at' )
                              $details=  json_encode($this->getOriginal($key));
                            $changes .= "Changed \"$key\" from \"{ $details }\" to \"$value\".\n";
                        }

                    }
                }
                if ($changes) {
                    SaleLog::create([
                        'user_id' => Auth::user() ? Auth::user()->id : null,
                        'sale_id' => $this->id,
                        'content' => $changes,
                    ]);
                }
            }
        } catch(\Exception $e) {
            //
        }

        return parent::save($options);
    }

    public function delete()
    {
        $this->returnItemsToStock();
        return parent::delete();
    }

    protected function returnItemsToStock()
    {
        foreach ($this->stock as $item) {
            if($item->batch_id != null)
                $item->returnToBatch();
            else
                $item->returnToStock();
        }
    }

    public function deliveryNote(){
        return $this->hasOne(DeliveryNotes::class,'sales_id','id');
    }
}
