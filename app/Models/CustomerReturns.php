<?php

namespace App\Models;

use App\Models\CustomerReturnItems;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class CustomerReturns extends Model
{
    protected $table="customer_returns";

    protected $fillable=['items_credited','value_of_credited','profile_lost'];


}
