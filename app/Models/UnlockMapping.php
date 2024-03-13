<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnlockMapping extends Model
{
    use HasFactory;
    protected $fillable = ['network', 'service_id', 'cost', 'make', 'model', 'cost'];

    public static function getAvailableNetworks()
    {
        return ['EE', 'EMEA', 'O2', 'Orange', 'T-Mobile', 'Virgin', 'Foreign Network', 'Vodafone', 'Not Applicable', 'Other', 'Unknown', 'Unlocked','EE Corporate', 'Three', 'AT&T', 'US GSM', 'T-Mobile USA', 'Sprint USA', 'All'];
    }

    public static function getAvailableDevices()
    {
        return [
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
            'iPhone 7 Plus',
            'iPhone 8',
            'iPhone 8 Plus',
            'iPhone X',
            'iPhone XS',
            'iPhone XS Max',
            'iPhone XR',
            'Samsung'
        ];
    }

    // for Samsung as there's one service for all, see #1359
    public static function getAvailableMakes()
    {
        return ['Samsung'];
    }

    public function getCostFormattedAttribute()
    {
        return money_format($this->cost);

    }
}
