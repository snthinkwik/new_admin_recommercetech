<?php

namespace App\Exports;

use App\Models\Stock;
use App\Models\StockTake;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;


class LostItemsExportCsv implements  FromArray,WithHeadings
{
    public function array(): array
    {
        $cont = "This item was removed from stock and marked as lost";
        $items = Stock::with(['stockLogs' => function ($query) use ($cont) {
            return $query->where('content', 'not like', '%' . $cont . '%');
        }])
            ->where('status', Stock::STATUS_LOST)
            ->get();

        $stock=[];
        foreach ($items as $item) {
            $stock[] = [
                'RCT Ref' => $item->our_ref,
                'Make' => $item->make,
                'Model' => $item->name,
                'Colour' => $item->colour,
                'IMEI' => $item->imei,
                'Serial Number' => $item->serial,
                'Purchase Value' => $item->purchase_price_formatted,
                'Date Marked as Lost' => $item->marked_as_lost ? $item->marked_as_lost->format('d/m/Y H:i') : '',
                'Lost Reason' => $item->lost_reason,
                'Last Activity Date' =>count($item->stockLogs)? $item->stockLogs[0]['created_at']:'',
                'Last User ' => count($item->stockLogs) ? $item->stockLogs[0]->user->full_name : 'System change',
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
            'Make',
            'Model',
            'Colour',
            'IMEI',
            'Serial Number',
            'Purchase Value',
            'Date Marked as Lost',
            'Lost Reason',
            'Last Activity Date',
            'Last User',

        ];



    }

}
