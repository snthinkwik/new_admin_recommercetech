<?php

namespace App\Models\Unlock;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pricing extends Model
{
    use HasFactory;

    const MODEL_BELOW_IPHONE_7 = 'Up to iPhone 6S/6S+';
    const MODEL_IPHONE_7 = 'iPhone 7';

    protected $table = 'unlock_pricing';

    protected $fillable = ['network', 'models', 'amount_before_vat'];

    protected $casts = ['amount' => 'float'];

    /**
     * @var array
     */
    protected static $availableNetworks;

    /**
     * @var array
     */
    protected static $availableModels;

    public function scopeGroupByAmount(Builder $query)
    {
        $query->groupBy('amount_before_vat')
            ->groupBy('models')
            ->selectRaw("*, group_concat(distinct network order by network separator '/') networks")
            ->orderBy('networks');
    }

    public static function getAvailableNetworks()
    {
        if (!self::$availableNetworks) {
            self::$availableNetworks = Pricing::distinct('network')->orderBy('network')->pluck('network')->toArray();
        }

        return self::$availableNetworks;
    }

    public static function getAvailableModels()
    {
        if (!self::$availableModels) {
            self::$availableModels = Pricing::distinct('models')->orderBy('models')->pluck('models')->toArray();
        }

        return self::$availableModels;
    }

    public function getAmountAttribute()
    {
        return round($this->amount_before_vat * 1.2, 2);
    }

    public function getAmountFormattedAttribute()
    {
        return money_format($this->amount);

    }

    public function getAmountBeforeVatFormattedAttribute()
    {
        return money_format($this->amount_before_vat);

    }
}
