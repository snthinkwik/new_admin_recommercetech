<?php

namespace App\Models;

use App\Stock;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImeiReport extends Model
{
    use HasFactory;
    const TYPE_NETWORK = 'network';
    const TYPE_UNLOCK = 'unlock';
    const TYPE_CHECK = 'check'; //this type is used for unlocks processing over 5 days (see issue #1018)

    const STATUS_PENDING = 'pending';
    const STATUS_DONE = 'done';
    const STATUS_CREATION_ERROR  = 'report creation error';
    const STATUS_PERMANENT_ERROR  = 'permanent error';

    protected $fillable = ['type', 'external_id', 'status', 'stock_id', 'report_status', 'report', 'imei', 'mobicode'];

    /**
     * @return bool|string False if not mapped, network name as string otherwise.
     */
    public function mapNetwork()
    {
        $networkMappings = Stock::getGsmFusionNetworkMapping();

        preg_match(
            '/Next Tether Activation Policy Description\s*:\s+(?<network>.*?)(\.|\s*Policy|\s*Bluetooth)/',
            $this->report,
            $networkMatch
        );

        if ($networkMatch && isset($networkMappings[$networkMatch['network']])) {
            return $networkMappings[$networkMatch['network']];
        }
        else {
            preg_match(
                '/Next Tether Policy ID\s*:\s+(?<network>.*?)(\.|\s*Policy|\s*Bluetooth)/',
                $this->report,
                $networkMatch
            );

            if ($networkMatch && isset($networkMappings[$networkMatch['network']])) {
                return $networkMappings[$networkMatch['network']];
            }
        }

        return false;
    }

    public function scopeType(Builder $query, $type)
    {
        if (!in_array($type, [self::TYPE_NETWORK, self::TYPE_UNLOCK])) {
            throw new Exception("Incorrect IMEI report type \"$type\".");
        }
        $query->where('type', $type);
    }

    public function stock_item()
    {
        return $this->belongsTo(\App\Models\Stock::clearBootedModels(), 'stock_id');
    }
}
