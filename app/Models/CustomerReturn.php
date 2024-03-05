<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class CustomerReturn extends Model
{
    use HasFactory;
    use HasFactory;
    const STATUS_OPEN = "Open";
    const STATUS_RETURNED = "Returned";
    const STATUS_CLOSED_CREDITED = "Closed";
    const STATUS_RMA_ISSUED = 'RMA Issued';
    const STATUS_RECEIVED = 'Received';
    const STATUS_IN_REPAIR = 'In Repair';
    const STATUS_APPROVED_FOR_CREDIT = 'Approved for Credit';
    const STATUS_CREDITED = 'Credited';
    protected $table = 'customer_return';


    public function getDateReturnReceivedAttribute($value)
    {

        if ($value === "0000-00-00 00:00:00") {
            return '-';
        } else {
            return \Carbon\Carbon::parse($value)->format('y-m-d');
        }

    }

    public function getDateCreditedAttribute($value)
    {
        if ($value === "0000-00-00 00:00:00") {
            return '-';
        } else {
            return \Carbon\Carbon::parse($value)->format('y-m-d');
        }
    }

    public function getDateOfIssueAttribute($value)
    {
        if ($value === "0000-00-00 00:00:00") {
            return '-';
        } else {
            return \Carbon\Carbon::parse($value)->format('y-m-d');
        }
    }

    public function sales()
    {
        return $this->hasOne(Sale::class, 'id', 'sales_id');
    }

    public function scopeFromRequest(Builder $query, Request $request, $type = 'main')
    {

        if ($request->sort) {
            $query->orderBy($request->sort, $request->sortO);
        }

        if($request->platform){
            $query->where('sold_on_platform',$request->platform);
        }



        return $query;
    }

    public function customerReturnsItems(){
        return $this->hasMany(CustomerReturnItems::class,'sale_id','sales_id');
    }

}
