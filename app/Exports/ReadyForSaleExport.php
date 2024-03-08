<?php

namespace App\Exports;

use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ReadyForSaleExport implements FromArray,WithHeadings
{

    public function array(): array
    {

        $stockItems = Stock::where('status', Stock::STATUS_READY_FOR_SALE)->get();
        $stock = [];
        foreach ($stockItems as $item) {
            $report = $item->phone_check ? json_decode($item->phone_check->response) : null;
            $stock[] = [
                'Item Ref' => $item->our_ref,
                'Item Name' => $item->name,
                'Capacity' => $item->capacity_formatted,
                'Colour' => $item->colour,
                'Grade' => $item->grade,
                'Faults' => $report ? $report->Failed : ""
            ];
        }



        return [
            $stock
        ];


    }
    public function headings(): array
    {

        return [
            'Item Ref',
            'Item Name',
            'Capacity',
            'Colour',
            'Grade',
            'Faults'
        ];



    }
}
