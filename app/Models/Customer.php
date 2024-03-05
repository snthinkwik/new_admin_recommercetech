<?php

namespace App\Models;

use App\Models\Address;
use App\Models\BillingAddress;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;
    protected $appends = ['full_name', 'billing_address', 'shipping_address'];

    protected $fillable = [
        'external_id', 'first_name', 'last_name', 'company_name', 'email', 'phone', 'balance', 'display_name'
    ];

    public function getFullNameAttribute()
    {
        if ($this->display_name) {
            return $this->display_name;
        }
        elseif ($this->first_name) {
            return $this->first_name . ' ' . $this->last_name;
        }
        else {
            return $this->company_name;
        }
    }

    public function getFullNameWithIdAttribute()
    {
        return "[$this->id] $this->full_name";
    }

    public function getBillingAddressAttribute()
    {
        return isset($this->attributes['billing_address']) ? $this->attributes['billing_address'] : null;
    }

//	public function setBillingAddressAttribute(BillingAddress $billingAddress)
//	{
//		$this->attributes['billing_address'] = $billingAddress;
//	}

    public function getShippingAddressAttribute()
    {
        return isset($this->attributes['shipping_address']) ? $this->attributes['shipping_address'] : null;
    }

    public function setShippingAddressAttribute(\App\Models\Address $address)
    {
        $this->attributes['shipping_address'] = $address;
    }
}
