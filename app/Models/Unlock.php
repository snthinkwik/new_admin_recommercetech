<?php

namespace App\Models;

use App\Models\Mobicode\GsxCheck;
use App\Models\Stock;
use App\Models\StockLog;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unlock extends Model
{
    use HasFactory;

    const STATUS_NEW = 'New';
    const STATUS_PROCESSING = 'Processing';
    const STATUS_UNLOCKED = 'Unlocked';
    const STATUS_FAILED = 'Failed';
    const STATUS_REPROCESSING = 'Reprocessing';

    const FAILED_REASON_BLACKLISTED = "Blacklisted";
    const FAILED_REASON_UNDER_6_MONTHS_OLD = "Under 6 Months Old";
    const FAILED_REASON_WRONG_NETWORK = "Wrong Network";
    const FAILED_REASON_CORPORATE_DEVICE = "Corporate Device";
    const FAILED_REASON_NOT_UNBRICKED = "Not Unbricked";

    protected $fillable = ['imei', 'network', 'status', 'status_description', 'completed_at', 'ebay_user_id', 'ebay_user_email'];

    protected $dates = ['completed_at'];

    /*public function __construct(array $attributes = array())
    {
        parent::__construct($attributes);
        // Database name prepended because of the bug in \Illuminate\Database\Eloquent::has()
        $this->table = DB::connection('stock')->getDatabaseName() . '.' . 'unlocks';
    }*/

    public function scopeImei(Builder $query, $imei)
    {
        if ($imei) {
            $query->where('imei', $imei);
        }
    }

    public function scopeStatus(Builder $query, $status)
    {
        if ($status) {
            $query->where('status', $status);
        }
    }

    public function scopeNetwork(Builder $query, $network)
    {
        if ($network) {
            $query->where('network', $network);
        }
    }

    /*public function orders()
    {
        return $this->belongsToMany('App\Unlock\Order', 'unlock_orders_unlocks', 'unlock_id', 'unlock_order_id');
    }*/

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function stock_item()
    {
        return $this->belongsTo(Stock::class, 'stock_id');
    }

    public function imei_report()
    {
        return $this->belongsTo(ImeiReport::class);
    }

    public function imei_report_check()
    {
        return $this->belongsTo(ImeiReport::class, 'imei_report_check_id');
    }

    public function gsx_check()
    {
            return $this->hasOne(GsxCheck::class);
    }

    public function getStatusTextClassAttribute()
    {
        switch ($this->status) {
            case self::STATUS_FAILED:
                return 'text-danger';
            case self::STATUS_UNLOCKED:
                return 'text-success';
            default:
                return '';
        }
    }

    public function getEtaAttribute()
    {
        $date = $this->created_at->copy();
        $duration = $this->network === 'O2' ? 3 : 2;
        $countdown = $duration;

        do {
            $date->addDay();
            if (!in_array($date->format('N'), [6, 7])) { // Not weekend
                $countdown--;
            }
        }
        while ($countdown);

        return $date;
    }

    public function getCostAddedFormattedAttribute()
    {
        return money_format($this->cost_added);

    }

    /*public function getHasRetailOrderAttribute()
    {
        if($this->orders()->first() && $this->orders()->first()->retailOrder)
            return true;
    }*/

    public function getTimerAttribute()
    {
        if($this->completed_at) {
            return $this->created_at->diffForHumans($this->completed_at, true);
        }

        return $this->created_at->diffForHumans(Carbon::now(), true);
    }

    public function getStatusDescriptionCodesAttribute()
    {
        return $this->imei_report->report;
    }

    public function save(array $options = array())
    {
        if(
            $this->stock_id && (!$this->exists || ($this->status !== $this->getOriginal('status')
                    && in_array($this->status, [self::STATUS_NEW, self::STATUS_FAILED]))
            )) {
            if($this->status ==  self::STATUS_NEW || !$this->status) {

                // old change
                // $unlock_cost = UnlockCost::where('network', $this->network)->first();

                $unlock_cost = null;

                if($this->stock_item) {
                    $unlock_cost = $this->stock_item->getUnlockMapping($this->network);
                }

                if($unlock_cost) {
                    $this->cost_added = $unlock_cost->cost;
                    $stock = $this->stock_item;
                    $oldUnlockCost = $stock->unlock_cost_formatted;
                    $stock->unlock_cost = $stock->unlock_cost + $unlock_cost->cost;
                    $stock->save();
                    $changes = "Unlock cost of: $unlock_cost->cost_formatted added to the Unlock Cost. Old $oldUnlockCost | New $stock->unlock_cost_formatted | Network: $unlock_cost->network, Service ID: $unlock_cost->service_id";
                    StockLog::create([
                        'stock_id' => $stock->id,
                        'content' => $changes
                    ]);
                }
            } elseif($this->status == self::STATUS_FAILED) {
                if($this->cost_added > 0) {
                    $stock = $this->stock_item;
                    $stock->unlock_cost = $stock->unlock_cost - $this->cost_added;
                    $stock->save();
                    $changes = "Unlock has failed due to: $this->status_description. $this->cost_added_formatted has been removed from the stock unlock cost and the unlock cost has been updated";
                    StockLog::create([
                        'stock_id' => $stock->id,
                        'content' => $changes
                    ]);
                    $this->cost_added = 0;
                }
            }





        }
        if (
            $this->getOriginal('status') === self::STATUS_PROCESSING &&
            in_array($this->status, [self::STATUS_UNLOCKED, self::STATUS_FAILED])
        ) {
            $this->completed_at = new Carbon();
        }

        if($this->stock_id && $this->status == self::STATUS_UNLOCKED && $this->getOriginal('status') != $this->status) {
            $stock = $this->stock_item;
            $message = "Unlock Status changed to Unlocked";
            if($stock->network != "Unlocked") {
                $stock->network = "Unlocked";
                $stock->save();
                $message .= " | Stock Network Updated";
            }
            StockLog::create([
                'stock_id' => $stock->id,
                'content' => $message
            ]);
        }

        if(isset($stock)){

            $stockDetatils=Stock::find($stock->id);
            $vatCalculation=calculationOfProfit($stock->sale_price,$stock->total_cost_with_repair,$stock->vat_type,$stock->purchase_price);

            $stockDetatils->profit=$vatCalculation['profit'];
            $stockDetatils->true_profit=$vatCalculation['true_profit'];
            $stockDetatils->marg_vat=$vatCalculation['marg_vat'];
            $stockDetatils->sale_vat=$vatCalculation['sale_vat'];
            $stockDetatils->total_price_ex_vat=$vatCalculation['total_price_ex_vat'];
            $stockDetatils->save();

        }



        return parent::save($options);
    }

    public static function getAvailableStatuses()
    {
        return [self::STATUS_NEW, self::STATUS_PROCESSING, self::STATUS_UNLOCKED, self::STATUS_FAILED];
    }

    public static function getAvailableStatusesWithKeys()
    {
        return array_combine(self::getAvailableStatuses(), self::getAvailableStatuses());
    }

    public static function getAvailableFailedReasons()
    {
        return [self::FAILED_REASON_BLACKLISTED, self::FAILED_REASON_UNDER_6_MONTHS_OLD, self::FAILED_REASON_WRONG_NETWORK, self::FAILED_REASON_CORPORATE_DEVICE, self::FAILED_REASON_NOT_UNBRICKED];
    }

    public static function getAvailabelFailedReasonsWithKeys()
    {
        return array_combine(self::getAvailableFailedReasons(), self::getAvailableFailedReasons());
    }
}
