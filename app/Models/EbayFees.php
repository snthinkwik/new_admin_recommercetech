<?php

namespace App\Models;

use App\Csv\Parser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\File\File;
use App\Models\ManualEbayFeeAssignment;
use App\Models\EbayOrders;

class EbayFees extends Model
{
    use HasFactory;
    protected $table = 'ebay_fees';

    const MATCHED_MANUALLY_ASSIGNED = "Manually assigned";
    const MATCHED_YES = "Yes";
    const MATCHED_NO = "No";
    const MATCHED_NA = "N/A";

    protected $fillable = [
        'title',
        'date',
        'item_number',
        'fee_type',
        'amount',
        'vin_serial_number',
        'received_top_rated_discount',
        'promotional_savings',
        'sales_record_number',
        'ebay_username',
        'matched',
        'formatted_fee_date',
        'matched_to_order_item',
        'created_at',
        'updated_at'
    ];

    public static function parseValidateCsv(File $csv, $salesPriceRequired = false) {

        $csvParser = new Parser($csv->getRealPath(), [
            'headerFilter' => function ($columnName) {
                $columnName = strtolower($columnName);

                $columnName = preg_replace('/\W+/', '_', $columnName);
                return $columnName;
            },
        ]);

        $rows = $csvParser->getAllRows();
        $errors = [];
        foreach ($rows as $i => $row) {

            $rules = [
                //  'sales_record_number' => 'required',
                //   'order_number' => 'required'
            ];

            $validator = Validator::make($row, $rules);
            if ($validator->fails()) {
                $errors[] = ['rowIdx' => $i, 'errors' => $validator->errors()];
            }
        }

        return [$rows, $errors];
    }

    public function scopeFromRequest(Builder $query, Request $request, $type = 'main') {
        if ($request->sort) {
            $query->orderBy($request->sort, $request->sortO);
        }
        if ($request->item_number) {
            $query->where('item_number', 'like', "%$request->item_number%");
        }
        if ($request->date) {
            $query->whereRaw("DATE_FORMAT(formatted_fee_date,'%Y-%m-%d') ='" . $request->date . "'");
        }
        if ($request->fee_type) {
            $query->where('fee_type', $request->fee_type);
        }
        if ($request->matched) {
            if ($request->matched == \App\EbayFees::MATCHED_YES) {
                $query->whereIn('matched', [$request->matched, EbayFees::MATCHED_MANUALLY_ASSIGNED]);
            } else {
                $query->where('matched', $request->matched);
            }
        }
        if ($request->field && $request->filter_value) {
            $query->where($request->field, 'like', "%$request->filter_value%");
        }
        return $query;
    }

    public function ManualEbayFeeAssignment() {
        return $this->hasOne( ManualEbayFeeAssignment::class, 'fee_record_no', 'id');
    }

    public function EbayOrders() {
        return $this->hasOne( EbayOrders::class, 'sales_record_number', 'sales_record_number');
    }

    public function order_items() {
        return $this->hasOne(EbayOrderItems::class, 'sales_record_number', 'sales_record_number');
    }

    public static function getAvailableMatched() {
        return [self::MATCHED_YES, self::MATCHED_NA, self::MATCHED_NO];
    }

    public static function getAvailableMatchedWithKeys() {
        return array_combine(self::getAvailableMatched(), self::getAvailableMatched());
    }

    public static function getEbayFees($owner = "", $start_date = '', $end_date = "") {
        $query = EbayFees::whereIn(\DB::raw('CAST(sales_record_number AS CHAR)'), array_map('current', \App\EbayOrderItems::where("owner", $owner)
            ->select("sales_record_number")
            ->get()
            ->toArray()));


        if ($start_date && $end_date) {
            $query->whereRaw('DATE_FORMAT(formatted_fee_date,"%Y-%m-%d") > ? AND DATE_FORMAT(formatted_fee_date,"%Y-%m-%d") < ?', [date('Y-m-d', strtotime($start_date)), date('Y-m-d', strtotime($end_date))]);
        }

        return $query->select(\DB::raw("SUM(trim(replace(amount,'£',''))) as total_fees"))
            ->first();
    }
}
