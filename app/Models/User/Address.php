<?php

namespace App\Models\User;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\Address as BaseAddress;

class Address extends BaseAddress
{
    use HasFactory;


    //protected $table = 'user_addresses';
    protected $fillable = [
        'line1', 'line2', 'city', 'county', 'postcode', 'country'
    ];
    public function __construct(array $attributes = array()) {
        parent::__construct($attributes);
        // Database name prepended because of the bug in \Illuminate\Database\Eloquent::has()
        $this->table = DB::connection()->getDatabaseName() . '.' . 'user_addresses';
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    public static function getCountryName($id) {
        return Address::where('user_id', $id)->first();
    }


}
