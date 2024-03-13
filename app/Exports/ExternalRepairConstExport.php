<?php

namespace App\Exports;

use App\Models\RepairsItems;
use App\Models\Stock;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;


class ExternalRepairConstExport implements FromArray,WithHeadings
{


    public function __construct($id)
    {
        $this->id = $id;
    }

    public function array(): array
    {


        $repairItem = RepairsItems::with('repair')->where('repair_id', $this->id)->where('type', RepairsItems::TYPE_EXTERNAL)->get();

        $items=[];
        foreach ($repairItem as $item) {

            $items[] = [
                'Repair Id' => $item->repair_id,
                'Engineer' => $item->repair ? $item->repair->Repairengineer->name : '',
                'Stock Id' => $item->stock->id,
                'Item Name' => $item->stock->name,
                'Item Status' => $item->stock->status,
                'Capacity' => $item->stock->capacity_formatted,
                'Test Status' => $item->stock->test_status,
                'Touch/Face ID Working?' => $item->stock->touch_id_working,
                'Cracked Back' => $item->stock->cracked_back,
                'Network' => $item->stock->network,
                'IMEI/Serial' => $item->stock->imei !== '' ? $item->stock->imei : $item->stock->serial,
                'Original Faults' => $item->original_faults,
                'Estimate Repair Cost' => $item->estimate_repair_cost,
                'Type' => $item->type,
                'Actual Repair Cost' => $item->actual_repair_cost,
                'Vat Type' => $item->stock->vat_type,
                'No. Days in Repair' => $item->closed_at ? $item->created_at->diffInDays($item->closed_at) : $item->created_at->diffInDays(Carbon::now()),
                'Status' => $item->status,
                'Repaired Faults' => $item->repaired_faults,
                'Created At' => $item->created_at,
                'Closed At' => $item->closed_at,
            ];
        }


        return [
            $items
        ];

    }
    public function headings(): array
    {

        return [
            'Repair Id',
            'Engineer',
            'Stock Id' ,
            'Item Name' ,
            'Item Status',
            'Capacity',
            'Test Status',
            'Touch/Face ID Working?',
            'Cracked Back',
            'Network' ,
            'IMEI/Serial',
            'Original Faults',
            'Estimate Repair Cost',
            'Type',
            'Actual Repair Cost',
            'Vat Type',
            'No. Days in Repair',
            'Status',
            'Repaired Faults',
            'Created At',
            'Closed At',
        ];



    }
}
