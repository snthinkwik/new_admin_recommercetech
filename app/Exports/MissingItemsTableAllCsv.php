<?php

namespace App\Exports;

use App\Http\Requests\Request;
use App\Models\RepairsItems;
use App\Models\Stock;
use App\Models\StockTake;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class MissingItemsTableAllCsv implements FromArray,WithHeadings
{


    public function __construct($request)
    {
        $this->days = $request->days;
    }

    public function array(): array
    {

        $days = $this->days ?: 7;
        $sub7Days = Carbon::now()->subDays($days)->startOfDay();
        $itemsLast7Days = StockTake::whereBetween('created_at', [$sub7Days, Carbon::now()])->get()->pluck('stock_id');
        $stockItems = Stock::whereIn('status', [Stock::STATUS_IN_STOCK, Stock::STATUS_READY_FOR_SALE])->whereNotIn('id', $itemsLast7Days)->orderBy('id', 'desc')->get();
        $stock=[];
        foreach ($stockItems as $item) {
            $report = $item->phone_check ? json_decode($item->phone_check->response) : null;
            $notes = $item->notes;
            //Ref 	3rd-party-ref 	IMEI 	Name 	Status
            $stock[] = [
                'RCT Ref' => $item->our_ref,
                'Name' => $item->name,
                'IMEI' => $report ? $report->IMEI : $item->imei,
                'Status' => $item->status,
                'Third Party Ref' => $item->third_party_ref,
                'PO Number' => $item->purchase_order_number,
                'Purchase Value' => $item->purchase_price_formatted
            ];
        }

        return [
            $stock
        ];


    }
    public function headings(): array
    {

        return [
            'RCT Ref',
            'Name',
            'IMEI',
            'Status',
            'Third Party Ref',
            'PO Number',
            'Purchase Value'
        ];



    }

}
