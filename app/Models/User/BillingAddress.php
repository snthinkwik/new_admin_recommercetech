<?php

namespace App\Models\User;

use App\Models\User;
use App\User\Address;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\BillingAddress as BaseAddress;


class BillingAddress extends BaseAddress
{
    use HasFactory;
    protected $fillable = [
        'line1', 'line2', 'city', 'county', 'postcode', 'country'
    ];

    public function __construct(array $attributes = array()) {
        parent::__construct($attributes);
        // Database name prepended because of the bug in \Illuminate\Database\Eloquent::has()
        $this->table = DB::connection()->getDatabaseName() . '.' . 'user_billing_address';
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    public static function getCountryName($id) {
        return \App\Models\User\Address::where('user_id', $id)->first();
    }
}
