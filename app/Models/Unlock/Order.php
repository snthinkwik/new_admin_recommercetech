<?php

namespace App\Models\Unlock;

use App\Unlock;
use App\Unlock\Pricing;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    const STATUS_NEW = 'New';
    const STATUS_PAID = 'Paid';
    const STATUS_COMPLETE = 'Complete';

    protected $table = 'unlock_orders';

    protected $fillable = ['imeis_awaiting_payment', 'network', 'models'];

    protected $casts = ['imeis_awaiting_payment' => 'array', 'amount' => 'float'];

    public function getAmountAttribute($value)
    {
        if (!$this->exists && !$value) {
            $value = $this->attributes['amount'] = $this->calculateAmount();
        }

        return $value;
    }
    public function getAmountBeforeVatAttribute()
    {
        return $this->amount/1.2;
    }

    public function getAmountFormattedAttribute()
    {
        return $this->amount;
        //return money_format(config('app.money_format'), $this->amount);
    }

    public function getImeisAttribute()
    {
        return $this->status === self::STATUS_NEW
            ? $this->imeis_awaiting_payment
            : $this->unlocks()->lists('imei');
    }

    public function unlocks()
    {
        return $this->belongsToMany('App\Unlock', 'unlock_orders_unlocks', 'unlock_order_id', 'unlock_id');
    }

    public function user()
    {
        return $this->belongsTo('App\User');
    }

    /*public function retailOrder()
    {
        return $this->belongsTo('App\Checkmynetwork\RetailOrder', 'id', 'stock_unlock_order_id');
    }*/

    protected function calculateAmount()
    {
        if (!$this->imeis_awaiting_payment) {
            throw new Exception("IMEIs not found for unlock order.");
        }

        $networkPricing = Pricing::where('network', $this->network)->where('models', $this->models)->first();
        if (!$networkPricing) {
            throw new Exception("Pricing not found for unlock order.");
        }

        return $networkPricing->amount * 100 * count($this->imeis_awaiting_payment) / 100;
    }

    protected function associateUnlocks($imeis)
    {
        $ids = [];
        foreach ($imeis as $imei) {
            $existing = Unlock::where('imei', $imei)->first();
            if ($existing) {
                $ids[] = $existing->id;
            }
            else {
                $unlock = new Unlock();
                $unlock->forceFill([
                    'user_id' => $this->user_id,
                    'imei' => $imei,
                    'network' => $this->network,
                ]);
                $unlock->save();
                $ids[] = $unlock->id;
            }
        }
        $this->unlocks()->sync($ids);
    }

    public function save(array $options = array())
    {
        if (!$this->exists) {
            $this->amount = $this->calculateAmount();
        }

        $wasPaid = in_array($this->getOriginal('status'), ['', self::STATUS_NEW]) && $this->status === self::STATUS_PAID;

        if ($wasPaid) {
            $imeis = $this->imeis_awaiting_payment;
            $this->imeis_awaiting_payment = [];
        }

        $ret = parent::save($options);

        if ($wasPaid) {
            $this->associateUnlocks($imeis);
        }

        return $ret;
    }
}
