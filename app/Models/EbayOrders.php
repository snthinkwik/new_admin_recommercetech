<?php

namespace App\Models;

use App\Csv\Parser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\File\File;

class EbayOrders extends Model
{
    use HasFactory;

    protected $table = 'master_ebay_orders';


    const STATUS_NEW = 'new';
    const STATUS_DISPATCHED = 'dispatched';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REFUNDED = 'refunded';
    const STATUS_AWAITING_PAYMENT = 'awaiting payment';

    protected $fillable = [
        'sales_record_number',
        'order_number',
        'buyer_name',
        'buyer_email',
        'buyer_note',
        'buyer_address_1',
        'buyer_address_2',
        'buyer_city',
        'buyer_county',
        'buyer_postcode',
        'buyer_country',
        'post_to_name',
        'post_to_phone',
        'post_to_address_1',
        'post_to_address_2',
        'post_to_city',
        'post_to_county',
        'post_to_postcode',
        'post_to_country',
        'postage_and_packaging',
        'total_price',
        'payment_method',
        'sale_date',
        'paid_on_date',
        'post_by_date',
        'paypal_transaction_id',
        'delivery_service',
        'tracking_number',
        'account_id',
        'billing_address3',
        'billing_address_company_name',
        'billing_address_country_code',
        'billing_phone_number',
        'currency_code',
        'discount_description',
        'invoice_emailed_date',
        'invoice_number',
        'invoice_printed_date',
        'reason',
        'shipping_address3',
        'shipping_address_company_name',
        'shipping_address_country_code',
        'shipping_alias',
        'shipping_email',
        'shipping_method',
        'status',
        'tag',
        'total_discount',
        'ebay_username',
        'paypal_fees',
        'packaging_materials',
        'payment_type'
    ];

    public static function parseValidateCsv(File $csv, $salesPriceRequired = false) {

        $csvParser = new Parser($csv->getRealPath(), [
            'headerFilter' => function($columnName) {
                $columnName = strtolower($columnName);

                $columnName = preg_replace('/\W+/', '_', $columnName);
                return $columnName;
            },
        ]);

        $rows = $csvParser->getAllRows();
        $errors = [];
        foreach ($rows as $i => $row) {

            $rules = [
                'sales_record_number' => 'required',
                'order_number' => 'required'
            ];

            $validator = Validator::make($row, $rules);
            if ($validator->fails()) {
                $errors[] = ['rowIdx' => $i, 'errors' => $validator->errors()];
            }
        }

        return [$rows, $errors];
    }

    public function scopeFromRequest(Builder $query, Request $request, $type = 'main') {

        if ($request->sort) {
            $query->orderBy($request->sort, $request->sortO);
        }

        if($request->category){
            $query->whereHas('EbayOrderItems.stock.product', function($q) use ($request) {
                return $q->where('category',$request->category);
            });
        }
        if ($request->allocated) {
            if ($request->allocated == "No"){
                $query->whereHas('EbayOrderItems', function($q) use ($request) {
                    return $q->whereNull('stock_id');
                });
            }else{
                $query->whereHas('EbayOrderItems', function($q) use ($request) {
                    return $q->whereNotNull('stock_id');
                });
            }

        }

        if (($request->field == "item_sku" || $request->field == "item_number") && $request->sales_record) {
            $query->whereHas('EbayOrderItems', function($q) use ($request) {
                return $q->where($request->field, $request->sales_record);
            });
        } else if ($request->field == "item_name" && $request->sales_record) {
            $query->whereHas('EbayOrderItems', function($q) use ($request) {
                return $q->where($request->field, 'like', "%$request->sales_record%");
            });
        } else if ($request->field && $request->sales_record) {
            $query->where($request->field, 'like', "%$request->sales_record%");
        }

        return $query;
    }

    public function EbayOrderItems() {
        return $this->hasMany('App\EbayOrderItems', 'order_id', 'id');
    }

    public function Newsale(){
        return $this->hasOne('App\Sale','id','new_sale_id');

    }


    public function orderItem()
    {
        return $this->belongsToMany('App\EbayOrderItems', 'ebay_order_items','order_id','id');
    }

    public function DpdImport() {
        return $this->hasMany('App\DpdInvoice', 'matched', 'sales_record_number');
    }

    public function getDataByOrderNumber($orderNumber) {
        return \App\EbayOrders::where('order_number', $orderNumber)->first();
    }

    public function EbayFees() {
        return $this->hasMany('App\EbayFees', 'sales_record_number', 'sales_record_number');
    }

    public function DpdInvoice() {
        return $this->hasMany('App\DpdInvoice', 'parcel_number', 'tracking_number');
    }

    public function EbayDeliveryCharges() {
        return $this->hasOne('App\EbayDeliveryCharges', 'sales_record_number', 'sales_record_number');
    }

    public function ebayRefund() {
        return $this->hasOne('App\EbayRefund', 'order_id', 'id');
    }

    public static function getAvailableStatus() {
        return [self::STATUS_NEW, self::STATUS_DISPATCHED, self::STATUS_CANCELLED, self::STATUS_REFUNDED, self::STATUS_AWAITING_PAYMENT];
    }

    public static function getAvailableStatusWithKeys() {
        return array_combine(self::getAvailableStatus(), self::getAvailableStatus());
    }

    public static function rules() {
        $rules = [
            'sales_record_number' => 'required',
            'order_number' => 'required'
        ];

        return $rules;
    }

    /**
     * We need to override the connection for the 'unique' rule in rules().
     */
    protected function getValidatorInstance() {
        $validator = parent::getValidatorInstance();
        $verifier = app('validation.presence');
        $validator->setPresenceVerifier($verifier);
        return $validator;
    }
}
