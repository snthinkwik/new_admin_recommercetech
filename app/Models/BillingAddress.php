<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillingAddress extends Model
{
    use HasFactory;
    protected $table='user_billing_address';
    protected $fillable = [
        'line1', 'line2', 'city', 'county', 'postcode', 'country'
    ];

    public function getFullAttribute()
    {
        $address = '';
        foreach (['line1', 'line2', 'city', 'county', 'postcode', 'country'] as $field) {
            if ($this->$field) {
                $address .= "{$this->$field}\n";
            }
        }
        return $address;
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id', 'user_id');
    }


    public function country_details()
    {
        return $this->belongsTo(Country::class, 'country', 'name');
    }
}
