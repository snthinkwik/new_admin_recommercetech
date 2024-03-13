<?php

namespace App\Exports;

use App\Models\Sale;
use App\Models\Stock;
use App\Models\StockTake;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SalesSingleCsv implements FromArray,WithHeadings
{

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function array(): array
    {

        $sale = Sale::findOrFail($this->id);

        $items = $sale->stock;
        $stock=[];
        foreach ($items as $item) {
            $stock[] = [
                'RCT Ref' => $item->our_ref,
                'Device Name' => $item->name,
                'Capacity' => $item->capacity_formatted,
                'Network' => $item->network,
                'Grade' => $item->grade,
                'IMEI' => $item->imei,
                'Engineer Notes' => $item->notes,
                'Sales price' => $item->sale_price_formatted
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
            'Device Name',
            'Capacity',
            'Network',
            'Grade',
            'IMEI',
            'Engineer Notes',
            'Sales price'
        ];



    }

}
